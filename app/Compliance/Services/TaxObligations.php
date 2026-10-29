<?php

namespace App\Compliance\Services;

use App\Compliance\Models\Filing;
use App\Profiles\Enums\ClientsWithhold;
use App\Profiles\Models\TaxProfile;
use App\Tax\Enums\Regime;
use App\Tax\Services\FilingCalendar;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Enums\CertificateStatus;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * What the taxpayer has to do next: the return to pay, the deadlines coming
 * up, and the checklist for a quarter.
 *
 * @phpstan-import-type QuarterResult from TaxCalculationService
 * @phpstan-import-type YearResult from TaxCalculationService
 *
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
     * Deadlines from today on, soonest first.
     *
     * @param  YearResult  $year
     * @return list<array{date: string, title: string, amount: float|null, note: string|null, tone: 'default'|'action'}>
     */
    public function upcoming(TaxProfile $profile, array $year, CarbonImmutable $today, int $limit = 4): array
    {
        $y = $year['year'];
        $filedPercentageTax = Filing::query()->where('year', $y)->where('form', '2551Q')->pluck('quarter')->all();
        $items = [];

        foreach ($year['quarters'] as $quarter) {
            if ($quarter['status'] === 'filed') {
                continue;
            }

            $ended = $quarter['status'] !== 'open';
            $items[] = $this->deadline(
                $quarter['due_date'],
                $quarter['quarter'] === 4 ? "{$y} annual {$quarter['form']}" : "Q{$quarter['quarter']} {$quarter['form']} · file + pay",
                $ended ? $quarter['due'] : null,
                $ended ? null : ($quarter['quarter'] === 4 ? 'After Q4' : 'Estimate'),
            );

            if ($quarter['percentage_tax'] !== null && ! in_array($quarter['quarter'], $filedPercentageTax, true)) {
                $items[] = $this->deadline(
                    $this->calendar->percentageTaxDue($y, $quarter['quarter'])->toDateString(),
                    "Q{$quarter['quarter']} 2551Q · percentage tax",
                    $ended ? $quarter['percentage_tax'] : null,
                    $ended ? null : 'Estimate',
                );
            }
        }

        if ($profile->clients_withhold !== ClientsWithhold::No) {
            $items[] = $this->deadline($this->calendar->swornDeclarationDue($y + 1)->toDateString(), 'Sworn declarations to clients', null, 'Reminder');
        }

        $items[] = $this->deadline($this->calendar->incomeTaxDue($y + 1, 1)->toDateString(), 'Q1 '.($y + 1).' · tax method locks', null, 'Decide', 'action');

        $upcoming = array_values(array_filter($items, fn (array $item) => $item['date'] >= $today->toDateString()));
        usort($upcoming, fn (array $a, array $b) => strcmp($a['date'], $b['date']));

        return array_slice($upcoming, 0, $limit);
    }

    /**
     * @param  'default'|'action'  $tone
     * @return array{date: string, title: string, amount: float|null, note: string|null, tone: 'default'|'action'}
     */
    private function deadline(string $date, string $title, ?float $amount, ?string $note, string $tone = 'default'): array
    {
        return ['date' => $date, 'title' => $title, 'amount' => $amount, 'note' => $note, 'tone' => $tone];
    }

    /**
     * The quarter's to-do list, as shown on the compliance card.
     *
     * @param  QuarterResult  $quarter
     * @param  Collection<int, Transaction>  $transactions  the year's transactions
     * @return array{
     *     items: list<array{key: string, label: string, done: bool}>,
     *     done: int,
     *     total: int,
     *     missing_certificates: list<array{counterparty: string, amount: float}>,
     * }
     */
    public function checklist(TaxProfile $profile, int $year, array $quarter, Collection $transactions, CarbonImmutable $today): array
    {
        $inQuarter = $transactions->filter(fn (Transaction $t) => $t->quarter() === $quarter['quarter'] || $quarter['quarter'] === 4);
        $withheld = $inQuarter->filter(fn (Transaction $t) => (float) $t->tax_withheld > 0);
        $missing = $withheld
            ->filter(fn (Transaction $t) => $t->certificateStatus($today) === CertificateStatus::Missing)
            ->groupBy('counterparty')
            ->map(fn (Collection $rows, string $client) => ['counterparty' => $client, 'amount' => (float) $rows->sum('tax_withheld')])
            ->values()
            ->all();
        $received = $withheld->filter(fn (Transaction $t) => $t->certificateStatus($today) === CertificateStatus::Received)->count();
        $filing = Filing::query()->where('year', $year)->where('quarter', $quarter['quarter'])->where('form', $quarter['form'])->first();

        $items = [
            ['key' => 'income', 'label' => 'Income recorded', 'done' => $inQuarter->contains(fn (Transaction $t) => $t->isIncome())],
            ['key' => 'computed', 'label' => 'Tax computed', 'done' => $quarter['status'] !== 'open'],
            ['key' => 'certificates', 'label' => "Tax certificates: {$received} of {$withheld->count()}", 'done' => $received === $withheld->count()],
            $profile->regime === Regime::Graduated
                ? ['key' => 'percentage_tax', 'label' => 'Percentage tax filed (2551Q)', 'done' => Filing::query()->where('year', $year)->where('quarter', $quarter['quarter'])->where('form', '2551Q')->exists()]
                : ['key' => 'percentage_tax', 'label' => 'No percentage tax due', 'done' => true],
            ['key' => 'filed', 'label' => 'Return filed', 'done' => $filing !== null],
            ['key' => 'paid', 'label' => 'Tax paid', 'done' => $filing?->isPaid() ?? false],
        ];

        return [
            'items' => $items,
            'done' => count(array_filter($items, fn (array $item) => $item['done'])),
            'total' => count($items),
            'missing_certificates' => array_values($missing),
        ];
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
