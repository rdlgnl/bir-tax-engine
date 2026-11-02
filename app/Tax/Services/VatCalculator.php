<?php

namespace App\Tax\Services;

use App\Returns\Models\Filing;
use App\Transactions\Enums\TransactionType;
use App\Transactions\Enums\VatKind;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Quarterly VAT for a VAT-registered taxpayer: VAT on sales (output tax)
 * less VAT on purchases (input tax). Sales count on their invoice date
 * from April 27, 2024 (RR 3-2024); a service invoiced before then counts
 * when it's paid, even after (Sec. 7(a)). Input VAT counts only from purchase invoices in the taxpayer's
 * name; others are listed as excluded. Excess input VAT carries over to
 * the next quarter, from one year into the next too (NIRC Sec. 110(B)).
 *
 * @phpstan-type VatQuarter array{
 *     quarter: int,
 *     vatable_sales: float,
 *     zero_rated_sales: float,
 *     exempt_sales: float,
 *     output_vat: float,
 *     purchases: float,
 *     input_vat: float,
 *     excluded_count: int,
 *     excluded_vat: float,
 *     carried_over: float,
 *     payable: float,
 *     excess: float,
 *     due_date: string,
 *     status: 'open'|'due'|'overdue'|'filed',
 *     filed_on: string|null,
 *     amount_paid: float|null,
 * }
 */
class VatCalculator
{
    /** @var array<int, float> each year's excess input VAT left after Q4 */
    private array $yearEndExcess = [];

    public function __construct(private FilingCalendar $calendar) {}

    /**
     * @return array<int, VatQuarter>
     */
    public function forYear(int $year, ?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::now();
        $transactions = Transaction::query()->forTaxYear($year)->get();
        $filings = Filing::query()->where('year', $year)->where('form', '2550Q')->get()->keyBy('quarter');
        $carry = $this->carriedInto($year);
        $quarters = [];

        foreach ([1, 2, 3, 4] as $quarter) {
            $in = $this->inQuarter($transactions, $year, $quarter);
            $q = $this->quarter($quarter, $in, $carry);
            $due = $this->calendar->vatDue($year, $quarter);
            $periodEnd = CarbonImmutable::create($year, $quarter * 3)->endOfMonth();
            $filing = $filings->get($quarter);

            $quarters[$quarter] = $q + [
                'due_date' => $due->toDateString(),
                'status' => match (true) {
                    $filing !== null => 'filed',
                    $today->lessThanOrEqualTo($periodEnd) => 'open',
                    $today->greaterThan($due->endOfDay()) => 'overdue',
                    default => 'due',
                },
                'filed_on' => $filing?->filed_on->toDateString(),
                'amount_paid' => $filing?->amount_paid !== null ? (float) $filing->amount_paid : null,
            ];
            $carry = $q['excess'];
        }

        $this->yearEndExcess[$year] = $carry;

        return $quarters;
    }

    /**
     * The excess input VAT the year before ended with: it carries into this
     * year's first quarter. Nothing before the first year with records.
     */
    private function carriedInto(int $year): float
    {
        $first = Transaction::query()->min('occurred_on');

        if ($first === null || $year <= CarbonImmutable::parse($first)->year) {
            return 0.0;
        }

        if (! array_key_exists($year - 1, $this->yearEndExcess)) {
            $this->forYear($year - 1);
        }

        return $this->yearEndExcess[$year - 1];
    }

    /**
     * The quarter's sales and purchases behind the figures: by customer and
     * by supplier, as the SLSP lists them.
     *
     * @return array{sales: list<array{name: string, tin: string|null, invoices: int, vatable: float, zero_rated: float, exempt: float, output_vat: float}>, purchases: list<array{name: string, tin: string|null, invoices: int, amount: float, input_vat: float}>, excluded: list<array{name: string, date: string, amount: float, vat: float}>}
     */
    public function lists(int $year, int $quarter): array
    {
        $in = $this->inQuarter(Transaction::query()->forTaxYear($year)->orderBy('occurred_on')->get(), $year, $quarter);

        $sales = $in->filter(fn (Transaction $t) => $t->isIncome())->groupBy('counterparty')
            ->map(fn (Collection $group, string $name) => [
                'name' => $name,
                'tin' => $group->pluck('counterparty_tin')->filter()->last(),
                'invoices' => $group->count(),
                'vatable' => $this->sum($group->filter(fn (Transaction $t) => $this->kind($t) === VatKind::Vatable), 'amount'),
                'zero_rated' => $this->sum($group->filter(fn (Transaction $t) => $this->kind($t) === VatKind::ZeroRated), 'amount'),
                'exempt' => $this->sum($group->filter(fn (Transaction $t) => $this->kind($t) === VatKind::Exempt), 'amount'),
                'output_vat' => $this->sum($group, 'vat_amount'),
            ]);

        $purchases = $in->filter(fn (Transaction $t) => $this->claimable($t))->groupBy('counterparty')
            ->map(fn (Collection $group, string $name) => [
                'name' => $name,
                'tin' => $group->pluck('counterparty_tin')->filter()->last(),
                'invoices' => $group->count(),
                'amount' => $this->sum($group, 'amount'),
                'input_vat' => $this->sum($group, 'vat_amount'),
            ]);

        $excluded = $in->filter(fn (Transaction $t) => $this->excluded($t))
            ->map(fn (Transaction $t) => ['name' => $t->counterparty, 'date' => $t->occurred_on->toDateString(), 'amount' => (float) $t->amount, 'vat' => (float) $t->vat_amount]);

        return [
            'sales' => array_values($sales->sortByDesc('vatable')->all()),
            'purchases' => array_values($purchases->sortByDesc('input_vat')->all()),
            'excluded' => array_values($excluded->all()),
        ];
    }

    /**
     * @param  Collection<int, Transaction>  $in
     * @return array{quarter: int, vatable_sales: float, zero_rated_sales: float, exempt_sales: float, output_vat: float, purchases: float, input_vat: float, excluded_count: int, excluded_vat: float, carried_over: float, payable: float, excess: float}
     */
    private function quarter(int $quarter, Collection $in, float $carry): array
    {
        $sales = $in->filter(fn (Transaction $t) => $t->isIncome());
        $claimable = $in->filter(fn (Transaction $t) => $this->claimable($t));
        $excluded = $in->filter(fn (Transaction $t) => $this->excluded($t));
        $output = $this->sum($sales, 'vat_amount');
        $input = $this->sum($claimable, 'vat_amount');
        $net = round($output - $input - $carry, 2);

        return [
            'quarter' => $quarter,
            'vatable_sales' => $this->sum($sales->filter(fn (Transaction $t) => $this->kind($t) === VatKind::Vatable), 'amount'),
            'zero_rated_sales' => $this->sum($sales->filter(fn (Transaction $t) => $this->kind($t) === VatKind::ZeroRated), 'amount'),
            'exempt_sales' => $this->sum($sales->filter(fn (Transaction $t) => $this->kind($t) === VatKind::Exempt), 'amount'),
            'output_vat' => $output,
            'purchases' => $this->sum($claimable, 'amount'),
            'input_vat' => $input,
            'excluded_count' => $excluded->count(),
            'excluded_vat' => $this->sum($excluded, 'vat_amount'),
            'carried_over' => round($carry, 2),
            'payable' => max(0.0, $net),
            'excess' => max(0.0, -$net),
        ];
    }

    /**
     * What counts in a quarter: sales on their VAT date, purchases on theirs.
     *
     * @param  Collection<int, Transaction>  $transactions
     * @return Collection<int, Transaction>
     */
    private function inQuarter(Collection $transactions, int $year, int $quarter): Collection
    {
        return $transactions->filter(function (Transaction $t) use ($year, $quarter) {
            $on = $t->isIncome() ? $t->salesTaxDate() : $t->occurred_on;

            return $on !== null && $on->year === $year && $on->quarter === $quarter;
        });
    }

    /**
     * A sale recorded before VAT kinds existed counts as vatable.
     */
    private function kind(Transaction $t): VatKind
    {
        return $t->vat_kind ?? VatKind::Vatable;
    }

    /**
     * Input VAT the return may claim: a purchase invoice in the taxpayer's name showing VAT.
     */
    private function claimable(Transaction $t): bool
    {
        return $t->type === TransactionType::Expense && $t->has_valid_invoice && (float) $t->vat_amount > 0;
    }

    /**
     * VAT paid on a purchase whose invoice isn't in the taxpayer's name: it can't be claimed.
     */
    private function excluded(Transaction $t): bool
    {
        return $t->type === TransactionType::Expense && ! $t->has_valid_invoice && (float) $t->vat_amount > 0;
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     */
    private function sum(Collection $transactions, string $column): float
    {
        return round((float) $transactions->sum(fn (Transaction $t) => (float) $t->{$column}), 2);
    }
}
