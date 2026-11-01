<?php

namespace App\Compliance\Services;

use App\Compliance\Models\CompensationCertificate;
use App\Corporations\Models\Corporation;
use App\Corporations\Services\CorporateTax;
use App\Letters\Models\Letter;
use App\Loans\Models\Loan;
use App\Loans\Services\LoanTax;
use App\Payroll\Services\Payroll;
use App\Profiles\Enums\ClientsWithhold;
use App\Profiles\Enums\IncomeSource;
use App\Profiles\Models\TaxProfile;
use App\Returns\Models\Filing;
use App\Tax\Enums\Regime;
use App\Tax\Services\FilingCalendar;
use App\Tax\Services\TaxCalculationService;
use App\Tax\Services\VatCalculator;
use App\Transactions\Enums\CertificateStatus;
use App\Transactions\Models\Transaction;
use App\Withholding\Services\WithholdingAgent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * What the taxpayer has to do next: the return to pay, the deadlines coming
 * up, and the checklist for a quarter.
 *
 * @phpstan-import-type QuarterResult from TaxCalculationService
 * @phpstan-import-type YearResult from TaxCalculationService
 *
 * @phpstan-type Deadline array{
 *     date: string,
 *     statutory_date: string,
 *     title: string,
 *     kind: 'income_tax'|'percentage_tax'|'vat'|'withholding'|'dst'|'reminder'|'decision',
 *     status: 'open'|'due'|'overdue'|'filed'|'reminder',
 *     amount: float|null,
 *     note: string|null,
 *     tone: 'default'|'action',
 *     href: string|null,
 * }
 * @phpstan-type ChecklistItem array{
 *     key: string,
 *     label: string,
 *     done: bool,
 *     state: 'done'|'warning'|'todo',
 *     detail: string,
 *     action: 'transactions'|'tax'|'file'|'pay'|null,
 * }
 * @phpstan-type CertificateRow array{
 *     counterparty: string,
 *     quarter: int,
 *     amount: float,
 *     amount_paid: float,
 *     payment_date: string|null,
 *     status: 'received'|'missing'|'pending',
 *     received_on: string|null,
 *     expected_by: string,
 *     certificate_id: int|null,
 *     transaction_id: int,
 * }
 * @phpstan-type NextPayment array{
 *     state: 'empty'|'cannot_compute'|'due'|'overdue'|'clear'|'not_applicable',
 *     year: int,
 *     quarter: QuarterResult|null,
 *     estimate: float|null,
 *     next_deadline: string|null,
 *     days_until_next: int|null,
 * }
 */
class TaxObligations
{
    public function __construct(
        private TaxCalculationService $engine,
        private FilingCalendar $calendar,
        private VatCalculator $vat,
        private WithholdingAgent $agent,
        private LoanTax $loans,
        private Payroll $payroll,
        private CorporateTax $corporations,
    ) {}

    /**
     * The return to pay next, or why there isn't one.
     *
     * @param  YearResult  $year  the current year, already computed
     * @return NextPayment
     */
    public function nextPayment(TaxProfile $profile, array $year, CarbonImmutable $today): array
    {
        $result = [
            'state' => 'clear',
            'year' => $year['year'],
            'quarter' => null,
            'estimate' => null,
            'next_deadline' => null,
            'days_until_next' => null,
        ];

        if ($year['status'] === 'not_applicable') {
            return [...$result, 'state' => 'not_applicable'];
        }

        if ($year['status'] === 'cannot_compute') {
            return [...$result, 'state' => 'cannot_compute'];
        }

        // Last year's annual return is still payable in the first months.
        $previous = $this->hasIncome($year['year'] - 1)
            ? $this->engine->forYear($profile, $year['year'] - 1, $today)['quarters']
            : [];

        $payable = collect([$year['year'] - 1 => $previous, $year['year'] => $year['quarters']])
            ->flatMap(fn (array $quarters, int $taxYear) => array_map(
                fn (array $quarter) => ['year' => $taxYear, 'quarter' => $quarter],
                array_values($quarters),
            ))
            ->filter(fn (array $row) => in_array($row['quarter']['status'], ['due', 'overdue'], true))
            ->sortBy(fn (array $row) => $row['quarter']['due_date'])
            ->first();

        if ($payable !== null) {
            return [
                ...$result,
                'state' => $payable['quarter']['status'],
                'year' => $payable['year'],
                'quarter' => $payable['quarter'],
            ];
        }

        if ($year['gross'] <= 0) {
            $options = collect($this->engine->compare((float) $profile->expected_income, $profile->expected_expense_percent ?? 0)['options'])
                ->keyBy('key');

            return [
                ...$result,
                'state' => 'empty',
                'estimate' => $options[$this->optionKey($profile)]['total'],
            ];
        }

        $next = collect($year['quarters'])->first(fn (array $q) => $q['status'] === 'open');

        return [
            ...$result,
            'next_deadline' => $next['due_date'] ?? null,
            'days_until_next' => $next['days_left'] ?? null,
        ];
    }

    /**
     * Every deadline that comes from a tax year: its returns, plus the
     * reminders and decisions that fall early the next year.
     *
     * @param  YearResult  $year
     * @return list<Deadline>
     */
    public function deadlines(TaxProfile $profile, array $year, bool $includeFiled = false): array
    {
        $y = $year['year'];
        $filedPercentageTax = Filing::query()->where('year', $y)->where('form', '2551Q')->pluck('quarter')->all();
        $items = [];

        foreach ($year['quarters'] as $quarter) {
            if ($quarter['status'] === 'filed' && ! $includeFiled) {
                continue;
            }

            $ended = $quarter['status'] !== 'open';
            $items[] = $this->deadline(
                date: $quarter['due_date'],
                statutoryDate: $quarter['statutory_date'],
                title: $quarter['quarter'] === 4 ? "{$y} annual return ({$quarter['form']})" : "Q{$quarter['quarter']} {$quarter['form']} · file + pay",
                kind: 'income_tax',
                status: $quarter['status'],
                amount: $ended ? $quarter['due'] : null,
                note: $ended ? null : ($quarter['quarter'] === 4 ? 'After Q4' : 'Estimate'),
            );

            if ($quarter['percentage_tax'] === null) {
                continue;
            }

            $filed = in_array($quarter['quarter'], $filedPercentageTax, true);

            if ($filed && ! $includeFiled) {
                continue;
            }

            $date = $this->calendar->percentageTaxDue($y, $quarter['quarter'])->toDateString();
            $items[] = $this->deadline(
                date: $date,
                statutoryDate: $date,
                title: "Q{$quarter['quarter']} 2551Q · percentage tax",
                kind: 'percentage_tax',
                status: $filed ? 'filed' : ($ended ? 'due' : 'open'),
                amount: $ended ? $quarter['percentage_tax'] : null,
                note: $ended ? null : 'Estimate',
            );
        }

        if ($profile->vat_registered) {
            foreach ($this->vat->forYear($y) as $vat) {
                if ($vat['status'] === 'filed' && ! $includeFiled) {
                    continue;
                }

                $ended = $vat['status'] !== 'open';
                $items[] = $this->deadline(
                    date: $vat['due_date'],
                    statutoryDate: $vat['due_date'],
                    title: "Q{$vat['quarter']} 2550Q · VAT",
                    kind: 'vat',
                    status: $vat['status'],
                    amount: $ended ? $vat['payable'] : null,
                    note: $ended ? null : 'Estimate',
                    href: route('returns.show', [$y, $vat['quarter'], '2550Q'], absolute: false),
                );
            }
        }

        // The 1601-C each month someone is employed, even when nothing was withheld.
        foreach ($this->payroll->year($y) as $month) {
            if ($month['status'] === 'filed' && ! $includeFiled) {
                continue;
            }

            $items[] = $this->deadline(
                date: $month['due_date'],
                statutoryDate: $month['due_date'],
                title: CarbonImmutable::create($y, $month['month'])->format('F').' 1601-C · tax on salaries',
                kind: 'withholding',
                status: $month['status'],
                amount: $month['status'] === 'open' ? null : $month['withheld'],
                note: $month['status'] === 'open' ? 'So far' : null,
                href: route('payroll.month', [$y, $month['month']], absolute: false),
            );
        }

        // At year end: a 2316 for each employee and the 1604-C, both by January 31.
        $payroll = $this->payroll->annual($y);

        if ($payroll['months'] !== []) {
            $ungiven = array_filter($payroll['employees'], fn (array $e) => $e['given_on'] === null);

            if ($ungiven !== []) {
                $items[] = $this->deadline($payroll['due_date'], $payroll['due_date'],
                    count($ungiven) === 1 ? 'Give '.reset($ungiven)['name'].' their 2316' : 'Give your employees their 2316s',
                    'reminder', 'reminder', null, 'Reminder',
                    href: route('payroll', $y, absolute: false));
            }

            if ($payroll['status'] !== 'filed' || $includeFiled) {
                $items[] = $this->deadline(
                    date: $payroll['due_date'],
                    statutoryDate: $payroll['due_date'],
                    title: "{$y} 1604-C · list of employees",
                    kind: 'withholding',
                    status: $payroll['status'],
                    amount: null,
                    note: 'No payment',
                    href: route('payroll.annual', $y, absolute: false),
                );
            }
        }

        // Replying to a BIR letter, until it's dealt with.
        foreach (Letter::query()->whereYear('reply_by', $y)->whereNull('resolved_on')->get() as $letter) {
            $date = (string) $letter->reply_by?->toDateString();
            $items[] = $this->deadline($date, $date, 'Reply to the BIR: '.lcfirst($letter->kindLabel()), 'reminder', 'reminder', null, 'Letter',
                tone: 'action', href: route('letters.show', $letter, absolute: false));
        }

        // The 1702Q of a corporation the taxpayer runs.
        if (($corporation = Corporation::current()) !== null) {
            foreach ($this->corporations->quarters($corporation, $y) as $quarter) {
                if ($quarter['status'] === 'filed' && ! $includeFiled) {
                    continue;
                }

                $ended = $quarter['status'] !== 'open';
                $items[] = $this->deadline(
                    date: $quarter['due_date'],
                    statutoryDate: $quarter['statutory_date'],
                    title: "Q{$quarter['quarter']} 1702Q · {$corporation->name}",
                    kind: 'income_tax',
                    status: $quarter['status'],
                    amount: $ended && $quarter['has_figures'] ? max(0.0, $quarter['payable']) : null,
                    note: $ended && ! $quarter['has_figures'] ? 'Enter figures' : ($ended ? null : 'Corporation'),
                    href: route('corporation.quarter', [$y, $quarter['quarter']], absolute: false),
                );
            }
        }

        // Form 2000 for each loan the taxpayer files the DST on themselves.
        foreach (Loan::query()->whereYear('signed_on', $y)->where('lender_kind', 'person')->get() as $loan) {
            $dst = $this->loans->for($loan);

            if ($dst['status'] === 'filed' && ! $includeFiled) {
                continue;
            }

            $items[] = $this->deadline(
                date: $dst['due_date'],
                statutoryDate: $dst['due_date'],
                title: "Form 2000 · DST on the loan from {$loan->lender}",
                kind: 'dst',
                status: $dst['status'] === 'bank' ? 'due' : $dst['status'],
                amount: $dst['total'],
                note: null,
                href: route('loans.show', $loan, absolute: false),
            );
        }

        if ($profile->withholding_agent) {
            foreach ($this->withholding($y, $includeFiled) as $item) {
                $items[] = $item;
            }
        }

        // A job alongside the business: the employer's 2316 is due to the taxpayer by January 31.
        if ($profile->income_source === IncomeSource::Both && ! CompensationCertificate::query()->where('year', $y)->exists()) {
            $date = CarbonImmutable::create($y + 1, 1, 31)->toDateString();
            $items[] = $this->deadline($date, $date, 'Get your 2316'.($profile->employer_name ? " from {$profile->employer_name}" : ''), 'reminder', 'reminder', null, 'Reminder',
                href: route('compensation', $y, absolute: false));
        }

        if ($profile->clients_withhold !== ClientsWithhold::No) {
            $date = $this->calendar->swornDeclarationDue($y + 1)->toDateString();
            $items[] = $this->deadline($date, $date, 'Sworn declarations to clients', 'reminder', 'reminder', null, 'Reminder',
                href: route('sworn.show', $y, absolute: false));
        }

        $date = $this->calendar->incomeTaxDue($y + 1, 1)->toDateString();
        $items[] = $this->deadline($date, $date, 'Q1 '.($y + 1).' · tax method locks', 'decision', 'reminder', null, 'Decide', 'action');

        usort($items, fn (array $a, array $b) => strcmp($a['date'], $b['date']));

        return $items;
    }

    /**
     * A withholding agent's 0619-E for the first two months of each quarter,
     * 1601-EQ for the quarter and 1604-E for the year, once something was withheld.
     * REVIEW: whether a month or quarter with nothing withheld still needs a return.
     *
     * @return list<Deadline>
     */
    private function withholding(int $year, bool $includeFiled): array
    {
        $items = [];

        foreach ($this->agent->year($year) as $q) {
            foreach (array_slice($q['months'], 0, 2) as $m) {
                if ($m['withheld'] <= 0 || $m['due_date'] === null || ($m['status'] === 'filed' && ! $includeFiled)) {
                    continue;
                }

                $items[] = $this->deadline(
                    date: $m['due_date'],
                    statutoryDate: $m['due_date'],
                    title: CarbonImmutable::create($year, $m['month'])->format('F').' 0619-E · withholding',
                    kind: 'withholding',
                    status: $m['status'],
                    amount: $m['status'] === 'open' ? null : $m['withheld'],
                    note: $m['status'] === 'open' ? 'So far' : null,
                    href: route('withholding.month', [$year, $m['month']], absolute: false),
                );
            }

            if ($q['withheld'] <= 0 || ($q['status'] === 'filed' && ! $includeFiled)) {
                continue;
            }

            $items[] = $this->deadline(
                date: $q['due_date'],
                statutoryDate: $q['due_date'],
                title: "Q{$q['quarter']} 1601-EQ · withholding",
                kind: 'withholding',
                status: $q['status'],
                amount: $q['status'] === 'open' ? null : $q['still_due'],
                note: $q['status'] === 'open' ? 'So far' : null,
                href: route('withholding.quarter', [$year, $q['quarter']], absolute: false),
            );
        }

        $annual = $this->agent->annual($year);

        if ($annual['withheld'] > 0 && ($annual['status'] !== 'filed' || $includeFiled)) {
            $items[] = $this->deadline(
                date: $annual['due_date'],
                statutoryDate: $annual['due_date'],
                title: "{$year} 1604-E · list of payees",
                kind: 'withholding',
                status: $annual['status'],
                amount: null,
                note: 'No payment',
                href: route('withholding.annual', $year, absolute: false),
            );
        }

        return $items;
    }

    /**
     * Deadlines from today on, soonest first.
     *
     * @param  YearResult  $year
     * @return list<Deadline>
     */
    public function upcoming(TaxProfile $profile, array $year, CarbonImmutable $today, int $limit = 4): array
    {
        $upcoming = array_filter(
            $this->deadlines($profile, $year),
            fn (array $item) => $item['date'] >= $today->toDateString(),
        );

        return array_slice(array_values($upcoming), 0, $limit);
    }

    /**
     * @param  'income_tax'|'percentage_tax'|'vat'|'withholding'|'dst'|'reminder'|'decision'  $kind
     * @param  'open'|'due'|'overdue'|'filed'|'reminder'  $status
     * @param  'default'|'action'  $tone
     * @return Deadline
     */
    private function deadline(
        string $date,
        string $statutoryDate,
        string $title,
        string $kind,
        string $status,
        ?float $amount,
        ?string $note,
        string $tone = 'default',
        ?string $href = null,
    ): array {
        return [
            'date' => $date,
            'statutory_date' => $statutoryDate,
            'title' => $title,
            'kind' => $kind,
            'status' => $status,
            'amount' => $amount,
            'note' => $note,
            'tone' => $tone,
            'href' => $href,
        ];
    }

    /**
     * The quarter's to-do list, as shown on the compliance page and card.
     *
     * @param  QuarterResult  $quarter
     * @param  Collection<int, Transaction>  $transactions  the year's transactions
     * @return array{
     *     items: list<ChecklistItem>,
     *     done: int,
     *     total: int,
     *     missing_certificates: list<array{counterparty: string, amount: float}>,
     *     filing: array{id: int, filed_on: string, amount_paid: float|null, paid_on: string|null}|null,
     * }
     */
    public function checklist(TaxProfile $profile, int $year, array $quarter, Collection $transactions, CarbonImmutable $today): array
    {
        $inQuarter = $transactions->filter(fn (Transaction $t) => $t->quarter() === $quarter['quarter'] || $quarter['quarter'] === 4);
        $income = $inQuarter->filter(fn (Transaction $t) => $t->isIncome());
        $withheld = $inQuarter->filter(fn (Transaction $t) => (float) $t->tax_withheld > 0);
        $missing = $withheld
            ->filter(fn (Transaction $t) => $t->certificateStatus($today) === CertificateStatus::Missing)
            ->groupBy('counterparty')
            ->map(fn (Collection $rows, string $client) => ['counterparty' => $client, 'amount' => (float) $rows->sum('tax_withheld')])
            ->values()
            ->all();
        $received = $withheld->filter(fn (Transaction $t) => $t->certificateStatus($today) === CertificateStatus::Received)->count();
        $filing = Filing::query()->where('year', $year)->where('quarter', $quarter['quarter'])->where('form', $quarter['form'])->first();
        $ended = $quarter['status'] !== 'open';
        $due = $this->peso($quarter['due']);
        $dueOn = CarbonImmutable::parse($quarter['due_date'])->format('M j');

        $certificatesDetail = match (true) {
            $withheld->isEmpty() => 'No tax withheld this quarter',
            $missing !== [] => 'Missing from '.collect($missing)->pluck('counterparty')->join(', ', ' and '),
            $received < $withheld->count() => 'Clients have until 20 days after the quarter',
            default => 'All received',
        };

        $items = [
            $this->item('income', 'Income recorded', $income->isNotEmpty(),
                $income->count().' '.Str::plural('payment', $income->count()).' · '.$this->peso((float) $income->sum('amount')),
                action: 'transactions'),
            $this->item('computed', 'Tax calculated', $ended,
                "{$due} · rule pack ".config('tax.version'), action: 'tax'),
            $this->item('certificates', "Tax certificates: {$received} of {$withheld->count()}", $received === $withheld->count(),
                $certificatesDetail, warning: $missing !== []),
            $profile->regime === Regime::Graduated
                ? $this->item('percentage_tax', 'Percentage tax filed (2551Q)',
                    Filing::query()->where('year', $year)->where('quarter', $quarter['quarter'])->where('form', '2551Q')->exists(),
                    $this->peso((float) $quarter['percentage_tax']).' · due '.$this->calendar->percentageTaxDue($year, $quarter['quarter'])->format('M j'))
                : $this->item('percentage_tax', 'No percentage tax due', true, '8% option replaces 2551Q'),
            $this->item('filed', 'Return filed', $filing !== null,
                $filing ? "Filed {$filing->filed_on->format('M j')}" : "{$quarter['form']} · due {$dueOn}",
                action: $filing === null && $ended ? 'file' : null),
            $this->item('paid', 'Tax paid', $filing?->isPaid() ?? false,
                $filing?->isPaid()
                    ? 'Paid '.$this->peso((float) $filing->amount_paid)." on {$filing->paid_on?->format('M j')}"
                    : "{$due} · any bank, e-wallet or BIR office",
                action: $filing !== null && ! $filing->isPaid() ? 'pay' : null),
        ];

        return [
            'items' => $items,
            'done' => count(array_filter($items, fn (array $item) => $item['done'])),
            'total' => count($items),
            'missing_certificates' => array_values($missing),
            'filing' => $filing ? [
                'id' => $filing->id,
                'filed_on' => $filing->filed_on->toDateString(),
                'amount_paid' => $filing->amount_paid !== null ? (float) $filing->amount_paid : null,
                'paid_on' => $filing->paid_on?->toDateString(),
            ] : null,
        ];
    }

    /**
     * The year's 2307s: what clients withheld, what is confirmed, and one row
     * per client per open quarter. Filed quarters fold into a single row.
     *
     * @param  Collection<int, Transaction>  $transactions  the year's transactions
     * @param  list<int>  $filedQuarters
     * @return array{
     *     credits: float,
     *     confirmed: float,
     *     waiting: float,
     *     rows: list<CertificateRow>,
     *     filed: array{quarters: list<int>, count: int, amount: float, all_received: bool}|null,
     * }
     */
    public function certificates(Collection $transactions, array $filedQuarters, CarbonImmutable $today): array
    {
        $withheld = $transactions->filter(fn (Transaction $t) => (float) $t->tax_withheld > 0);
        $isReceived = fn (Transaction $t) => $t->certificateStatus($today) === CertificateStatus::Received;
        $groups = $withheld->groupBy(fn (Transaction $t) => $t->quarter().'|'.$t->counterparty);

        $rows = [];

        foreach ($groups as $group) {
            if (! in_array($group->first()->quarter(), $filedQuarters, true)) {
                $rows[] = $this->certificateRow($group, $today);
            }
        }

        usort($rows, fn (array $a, array $b) => [$a['quarter'], $a['counterparty']] <=> [$b['quarter'], $b['counterparty']]);

        $filedGroups = $groups->filter(fn (Collection $group) => in_array($group->first()->quarter(), $filedQuarters, true));
        $credits = round((float) $withheld->sum('tax_withheld'), 2);
        $confirmed = round((float) $withheld->filter($isReceived)->sum('tax_withheld'), 2);

        return [
            'credits' => $credits,
            'confirmed' => $confirmed,
            'waiting' => round($credits - $confirmed, 2),
            'rows' => $rows,
            'filed' => $filedGroups->isEmpty() ? null : [
                'quarters' => array_values(array_unique($filedGroups->map(fn (Collection $g) => $g->first()->quarter())->sort()->values()->all())),
                'count' => $filedGroups->count(),
                'amount' => round((float) $filedGroups->flatten()->sum('tax_withheld'), 2),
                'all_received' => $filedGroups->flatten()->every($isReceived),
            ],
        ];
    }

    /**
     * One client's withheld payments in one quarter.
     *
     * @param  Collection<int, Transaction>  $group
     * @return CertificateRow
     */
    private function certificateRow(Collection $group, CarbonImmutable $today): array
    {
        $first = $group->firstOrFail();
        $statuses = $group->map(fn (Transaction $t) => $t->certificateStatus($today));
        $allReceived = $statuses->every(fn (CertificateStatus $s) => $s === CertificateStatus::Received);

        return [
            'counterparty' => $first->counterparty,
            'quarter' => $first->quarter(),
            'amount' => round((float) $group->sum('tax_withheld'), 2),
            'amount_paid' => round((float) $group->sum('amount'), 2),
            'payment_date' => $group->count() === 1 ? $first->occurred_on->toDateString() : null,
            'status' => match (true) {
                $allReceived => 'received',
                $statuses->contains(CertificateStatus::Missing) => 'missing',
                default => 'pending',
            },
            'received_on' => $allReceived
                ? $group->map(fn (Transaction $t) => (string) $t->certificate_received_on?->toDateString())->max()
                : null,
            'expected_by' => $first->occurred_on->lastOfQuarter()->addDays(config('tax.certificate_grace_days'))->toDateString(),
            'certificate_id' => $first->certificate_id,
            'transaction_id' => $first->id,
        ];
    }

    /**
     * @param  'transactions'|'tax'|'file'|'pay'|null  $action
     * @return ChecklistItem
     */
    private function item(string $key, string $label, bool $done, string $detail, bool $warning = false, ?string $action = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'done' => $done,
            'state' => $done ? 'done' : ($warning ? 'warning' : 'todo'),
            'detail' => $detail,
            'action' => $action,
        ];
    }

    private function peso(float $amount): string
    {
        return '₱'.Number::format($amount, maxPrecision: 2);
    }

    private function hasIncome(int $year): bool
    {
        return Transaction::query()->forYear($year)->exists();
    }

    private function optionKey(TaxProfile $profile): string
    {
        return match (true) {
            $profile->regime === Regime::EightPercent => 'eight_percent',
            $profile->deduction_method?->value === 'itemized' => 'graduated_itemized',
            default => 'graduated_osd',
        };
    }
}
