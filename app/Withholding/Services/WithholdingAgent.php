<?php

namespace App\Withholding\Services;

use App\Returns\Models\Filing;
use App\Tax\Services\FilingCalendar;
use App\Transactions\Enums\TransactionType;
use App\Transactions\Models\Transaction;
use App\Withholding\Models\IssuedCertificate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The taxpayer as a withholding agent: the tax they kept from what they
 * paid others, remitted on 0619-E for the first two months of a quarter
 * and on 1601-EQ for the quarter, with a 2307 for each payee.
 *
 * @phpstan-type Status 'open'|'due'|'overdue'|'filed'
 * @phpstan-type Month array{month: int, withheld: float, due_date: string|null, status: Status, filed_on: string|null, amount_paid: float|null}
 * @phpstan-type Payee array{key: string, name: string, tin: string|null, atc: string, income: float, withheld: float, months: array{float, float, float}, given_on: string|null}
 * @phpstan-type AtcRow array{atc: string, label: string, rate: float, base: float, withheld: float}
 * @phpstan-type Quarter array{quarter: int, months: list<Month>, withheld: float, remitted: float, still_due: float, atcs: list<AtcRow>, payees: list<Payee>, due_date: string, status: Status, filed_on: string|null}
 */
class WithholdingAgent
{
    public function __construct(private FilingCalendar $calendar) {}

    /**
     * Every quarter of the year.
     *
     * @return array<int, Quarter>
     */
    public function year(int $year, ?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::now();
        $payments = $this->payments($year);
        $filings = Filing::query()->where('year', $year)->whereIn('form', ['0619-E', '1601-EQ'])->get();
        $given = IssuedCertificate::query()->where('year', $year)->get();
        $quarters = [];

        foreach ([1, 2, 3, 4] as $quarter) {
            $in = $payments->filter(fn (Transaction $t) => $t->quarter() === $quarter);
            $months = [];

            foreach ([1, 2, 3] as $n) {
                $month = $quarter * 3 - 3 + $n;
                $withheld = $this->sum($in->filter(fn (Transaction $t) => $t->occurred_on->month === $month), 'agent_withheld');

                // The quarter's last month goes on the 1601-EQ, not a 0619-E.
                if ($n === 3) {
                    $months[] = ['month' => $month, 'withheld' => $withheld, 'due_date' => null, 'status' => 'open', 'filed_on' => null, 'amount_paid' => null];

                    continue;
                }

                $filing = $filings->first(fn (Filing $f) => $f->form === '0619-E' && $f->month === $month);
                $due = $this->calendar->withholdingMonthlyDue($year, $month);
                $months[] = [
                    'month' => $month,
                    'withheld' => $withheld,
                    'due_date' => $due->toDateString(),
                    'status' => $this->status($filing, CarbonImmutable::create($year, $month)->endOfMonth(), $due, $today),
                    'filed_on' => $filing?->filed_on->toDateString(),
                    'amount_paid' => $filing?->amount_paid !== null ? (float) $filing->amount_paid : null,
                ];
            }

            $withheld = $this->sum($in, 'agent_withheld');
            // A month counts as remitted once its 0619-E is marked, for what was paid (or withheld).
            $remitted = round(array_sum(array_map(
                fn (array $m) => $m['status'] === 'filed' ? ($m['amount_paid'] ?? $m['withheld']) : 0.0,
                array_slice($months, 0, 2),
            )), 2);
            $filing = $filings->first(fn (Filing $f) => $f->form === '1601-EQ' && $f->quarter === $quarter);
            $due = $this->calendar->withholdingQuarterlyDue($year, $quarter);

            $quarters[$quarter] = [
                'quarter' => $quarter,
                'months' => $months,
                'withheld' => $withheld,
                'remitted' => $remitted,
                'still_due' => round(max(0.0, $withheld - $remitted), 2),
                'atcs' => $this->atcRows($in),
                'payees' => $this->payees($in, $quarter, $given),
                'due_date' => $due->toDateString(),
                'status' => $this->status($filing, CarbonImmutable::create($year, $quarter * 3)->endOfMonth(), $due, $today),
                'filed_on' => $filing?->filed_on->toDateString(),
            ];
        }

        return $quarters;
    }

    /**
     * One month's withholding, with each payment, for its 0619-E.
     *
     * @return array{withheld: float, payments: list<array{payee: string, atc: string, amount: float, withheld: float, date: string}>}
     */
    public function month(int $year, int $month): array
    {
        $in = $this->payments($year)->filter(fn (Transaction $t) => $t->occurred_on->month === $month);

        return [
            'withheld' => $this->sum($in, 'agent_withheld'),
            'payments' => array_values($in->map(fn (Transaction $t) => [
                'payee' => $t->counterparty,
                'atc' => (string) $t->atc,
                'amount' => (float) $t->amount,
                'withheld' => (float) $t->agent_withheld,
                'date' => $t->occurred_on->toDateString(),
            ])->all()),
        ];
    }

    public static function key(string $payee): string
    {
        return Str::slug($payee) ?: 'payee';
    }

    /**
     * Payments the taxpayer withheld from in the year.
     *
     * @return Collection<int, Transaction>
     */
    private function payments(int $year): Collection
    {
        return Transaction::query()->forYear($year)
            ->where('type', TransactionType::Expense)
            ->where('agent_withheld', '>', 0)
            ->orderBy('occurred_on')
            ->get();
    }

    /**
     * @param  Collection<int, Transaction>  $in
     * @return list<AtcRow>
     */
    private function atcRows(Collection $in): array
    {
        $atcs = (array) config('tax.withholding.atcs');

        return array_values($in->groupBy('atc')->map(fn (Collection $group, string $atc) => [
            'atc' => $atc,
            'label' => (string) ($atcs[$atc]['label'] ?? $atc),
            'rate' => (float) ($atcs[$atc]['rate'] ?? 0),
            'base' => $this->sum($group, 'amount'),
            'withheld' => $this->sum($group, 'agent_withheld'),
        ])->all());
    }

    /**
     * @param  Collection<int, Transaction>  $in
     * @param  Collection<int, IssuedCertificate>  $given
     * @return list<Payee>
     */
    private function payees(Collection $in, int $quarter, Collection $given): array
    {
        return array_values($in->groupBy('counterparty')->map(function (Collection $group, string $name) use ($quarter, $given) {
            $byMonth = fn (int $n) => $this->sum($group->filter(fn (Transaction $t) => $t->occurred_on->month === $quarter * 3 - 3 + $n), 'amount');

            return [
                'key' => self::key($name),
                'name' => $name,
                'tin' => $group->pluck('counterparty_tin')->filter()->last(),
                'atc' => (string) $group->last()?->atc,
                'income' => $this->sum($group, 'amount'),
                'withheld' => $this->sum($group, 'agent_withheld'),
                'months' => [$byMonth(1), $byMonth(2), $byMonth(3)],
                'given_on' => $given->first(fn (IssuedCertificate $c) => $c->quarter === $quarter && $c->payee === $name)?->given_on->toDateString(),
            ];
        })->sortBy('name')->all());
    }

    /**
     * @return Status
     */
    private function status(?Filing $filing, CarbonImmutable $periodEnd, CarbonImmutable $due, CarbonImmutable $today): string
    {
        return match (true) {
            $filing !== null => 'filed',
            $today->lessThanOrEqualTo($periodEnd) => 'open',
            $today->greaterThan($due->endOfDay()) => 'overdue',
            default => 'due',
        };
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     */
    private function sum(Collection $transactions, string $column): float
    {
        return round((float) $transactions->sum(fn (Transaction $t) => (float) $t->{$column}), 2);
    }
}
