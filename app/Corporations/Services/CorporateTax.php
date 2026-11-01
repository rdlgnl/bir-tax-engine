<?php

namespace App\Corporations\Services;

use App\Corporations\Models\Corporation;
use App\Corporations\Models\CorporationQuarter;
use App\Tax\Services\FilingCalendar;
use App\Tax\Services\TaxRules;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * A corporation's income tax: the regular rate (20% or 25%) on taxable
 * income, or the minimum corporate income tax (MCIT) on gross income when
 * that's higher, declared cumulatively on the 1702Q each quarter.
 *
 * Earlier quarters count as paid with what was recorded when they were
 * marked filed, or else with what they came to.
 *
 * @phpstan-type Figures array{sales: float, cost_of_sales: float, gross_from_operation: float, other_income: float, gross_income: float, deductions: float, taxable_income: float}
 * @phpstan-type Tax array{rate: float, small: bool, regular_tax: float, mcit_applies: bool, mcit: float, uses: 'regular'|'mcit', tax_due: float}
 * @phpstan-type QuarterTax array{
 *     year: int,
 *     quarter: int,
 *     has_figures: bool,
 *     this_quarter: Figures,
 *     to_date: Figures,
 *     previous_taxable_income: float,
 *     gross_by_quarter: list<float>,
 *     rate: float,
 *     small: bool,
 *     regular_tax: float,
 *     mcit_applies: bool,
 *     mcit: float,
 *     uses: 'regular'|'mcit',
 *     tax_due: float,
 *     paid_regular: float,
 *     paid_mcit: float,
 *     withheld_previous: float,
 *     withheld_this_quarter: float,
 *     credits: float,
 *     payable: float,
 *     due_date: string,
 *     statutory_date: string,
 *     due_note: string,
 *     status: 'open'|'due'|'overdue'|'filed',
 *     filed_on: string|null,
 *     reference_number: string|null,
 *     amount_paid: float|null,
 * }
 * @phpstan-type AnnualEstimate array{
 *     year: int,
 *     estimated: bool,
 *     quarters_entered: int,
 *     figures: Figures,
 *     withheld: float,
 *     withheld_previous: float,
 *     withheld_fourth_quarter: float,
 *     rate: float,
 *     small: bool,
 *     regular_tax: float,
 *     mcit_applies: bool,
 *     mcit: float,
 *     uses: 'regular'|'mcit',
 *     tax_due: float,
 *     paid_regular: float,
 *     paid_mcit: float,
 *     quarterly_payments: float,
 *     credits: float,
 *     payable: float,
 *     due_date: string,
 *     status: 'open'|'due'|'overdue'|'filed',
 *     filed_on: string|null,
 *     reference_number: string|null,
 *     amount_paid: float|null,
 * }
 */
class CorporateTax
{
    public function __construct(private FilingCalendar $calendar, private TaxRules $rules) {}

    /**
     * @return QuarterTax
     */
    public function quarter(Corporation $corporation, int $year, int $quarter): array
    {
        return $this->quarters($corporation, $year, $quarter)[$quarter];
    }

    /**
     * Quarters 1 to $upTo of a year, each building on the ones before.
     *
     * @return array<int, QuarterTax>
     */
    public function quarters(Corporation $corporation, int $year, int $upTo = 3): array
    {
        $rows = $this->rows($corporation, $year);
        $results = [];
        $toDate = $this->figures(null, $corporation);
        $grossByQuarter = [];
        $paidRegular = $paidMcit = $withheldBefore = 0.0;
        $today = CarbonImmutable::now();

        for ($q = 1; $q <= $upTo; $q++) {
            $row = $rows->get($q);
            $thisQuarter = $this->figures($row, $corporation);
            $previousTaxable = $toDate['taxable_income'];
            $toDate = $this->add($toDate, $thisQuarter);
            $grossByQuarter[] = $thisQuarter['gross_income'];
            $tax = $this->tax($corporation, $year, $toDate['taxable_income'], $grossByQuarter);
            $withheld = (float) ($row->withheld ?? 0);
            $credits = round($paidRegular + $paidMcit + $withheldBefore + $withheld, 2);
            $statutory = $this->quarterStatutoryDate($year, $q);
            $due = $this->calendar->nextWorkingDay($statutory);

            $results[$q] = [
                'year' => $year,
                'quarter' => $q,
                'has_figures' => $row !== null,
                'this_quarter' => $thisQuarter,
                'to_date' => $toDate,
                'previous_taxable_income' => $previousTaxable,
                'gross_by_quarter' => $grossByQuarter,
                ...$tax,
                'paid_regular' => $paidRegular,
                'paid_mcit' => $paidMcit,
                'withheld_previous' => $withheldBefore,
                'withheld_this_quarter' => $withheld,
                'credits' => $credits,
                'payable' => round($tax['tax_due'] - $credits, 2),
                'due_date' => $due->toDateString(),
                'statutory_date' => $statutory->toDateString(),
                'due_note' => $this->dueNote($this->quarterEnd($year, $q), $statutory, $due),
                'status' => match (true) {
                    $row?->filed_on !== null => 'filed',
                    $today->lessThanOrEqualTo($this->quarterEnd($year, $q)->endOfDay()) => 'open',
                    $today->greaterThan($due->endOfDay()) => 'overdue',
                    default => 'due',
                },
                'filed_on' => $row?->filed_on?->toDateString(),
                'reference_number' => $row?->reference_number,
                'amount_paid' => $row?->amount_paid !== null ? (float) $row->amount_paid : null,
            ];

            $paid = $results[$q]['amount_paid'] ?? max(0.0, $results[$q]['payable']);
            $tax['uses'] === 'mcit' ? $paidMcit += $paid : $paidRegular += $paid;
            $withheldBefore += $withheld;
        }

        return $results;
    }

    /**
     * The 1702-RT for a year. Until the 4th quarter is in the books, the
     * year is estimated from the quarters so far, as if the rest go the same.
     * The 4th quarter has no 1702Q; its row records the 1702-RT once filed.
     *
     * @return AnnualEstimate|null
     */
    public function annual(Corporation $corporation, int $year): ?array
    {
        $rows = $this->rows($corporation, $year);

        if ($rows->isEmpty()) {
            return null;
        }

        $estimated = ! $rows->has(4);
        $factor = $estimated ? 4 / $rows->count() : 1.0;
        $figures = $this->figures(null, $corporation);

        foreach ($rows as $row) {
            $figures = $this->add($figures, $this->figures($row, $corporation));
        }

        // Each quarter's gross income, the missing ones as the average of those entered.
        $entered = $rows->map(fn (CorporationQuarter $row) => $this->figures($row, $corporation)['gross_income']);
        $grossByQuarter = array_map(fn (int $q) => $entered->get($q, round($entered->avg() ?? 0.0, 2)), [1, 2, 3, 4]);
        $figures = array_map(fn (float $amount) => round($amount * $factor, 2), $figures);
        $withheld = round($rows->sum(fn (CorporationQuarter $row) => (float) $row->withheld) * $factor, 2);
        $withheldBefore = round($rows->filter(fn (CorporationQuarter $row) => $row->quarter < 4)->sum(fn (CorporationQuarter $row) => (float) $row->withheld), 2);
        $tax = $this->tax($corporation, $year, $figures['taxable_income'], $grossByQuarter);
        $paidRegular = $paidMcit = 0.0;

        foreach ($this->quarters($corporation, $year) as $quarter) {
            if ($quarter['has_figures']) {
                $paid = $quarter['amount_paid'] ?? max(0.0, $quarter['payable']);
                $quarter['uses'] === 'mcit' ? $paidMcit += $paid : $paidRegular += $paid;
            }
        }

        $payments = round($paidRegular + $paidMcit, 2);
        $credits = round($payments + $withheld, 2);
        $due = $this->calendar->incomeTaxDue($year, 4);
        $fourth = $rows->get(4);
        $today = CarbonImmutable::now();

        return [
            'year' => $year,
            'estimated' => $estimated,
            'quarters_entered' => $rows->count(),
            'figures' => $figures,
            'withheld' => $withheld,
            'withheld_previous' => $withheldBefore,
            'withheld_fourth_quarter' => round($withheld - $withheldBefore, 2),
            ...$tax,
            'paid_regular' => round($paidRegular, 2),
            'paid_mcit' => round($paidMcit, 2),
            'quarterly_payments' => $payments,
            'credits' => $credits,
            'payable' => round($tax['tax_due'] - $credits, 2),
            'due_date' => $due->toDateString(),
            'status' => match (true) {
                $fourth?->filed_on !== null => 'filed',
                $today->lessThanOrEqualTo($this->quarterEnd($year, 4)->endOfDay()) => 'open',
                $today->greaterThan($due->endOfDay()) => 'overdue',
                default => 'due',
            },
            'filed_on' => $fourth?->filed_on?->toDateString(),
            'reference_number' => $fourth?->reference_number,
            'amount_paid' => $fourth?->amount_paid !== null ? (float) $fourth->amount_paid : null,
        ];
    }

    /**
     * The 1702Q to file next: the earliest of this year's first three
     * quarters that has ended and isn't marked filed.
     */
    public function nextQuarter(Corporation $corporation, CarbonImmutable $today): ?int
    {
        $filed = $this->rows($corporation, $today->year)->filter(fn (CorporationQuarter $row) => $row->filed_on !== null)->keys();

        for ($q = 1; $q <= 3; $q++) {
            if ($today->greaterThan($this->quarterEnd($today->year, $q)) && ! $filed->contains($q)) {
                return $q;
            }
        }

        return null;
    }

    /**
     * The year MCIT first applies: the 4th taxable year after the year
     * operations began.
     */
    /**
     * Whether the rule pack has corporate rates for the whole of a year.
     */
    public function covers(int $year): bool
    {
        return $this->rules->covers($year) && $this->rules->forQuarter($year, 1)->has('corporation.rate');
    }

    /**
     * The MCIT rate in force for a quarter, or for the year's last quarter.
     */
    public function mcitRate(int $year, int $quarter = 4): float
    {
        return $this->rules->forQuarter($year, $quarter)->rate('corporation.mcit_rate');
    }

    public function mcitFrom(Corporation $corporation): int
    {
        return $corporation->operations_began + (int) config('tax.corporation.mcit_from_year');
    }

    public function quarterEnd(int $year, int $quarter): CarbonImmutable
    {
        return CarbonImmutable::create($year, $quarter * 3)->endOfMonth()->startOfDay();
    }

    /**
     * "60 days after Sep 30 is Sun, Nov 29; Nov 30 is Bonifacio Day, so it
     * moves to Dec 1."
     */
    private function dueNote(CarbonImmutable $quarterEnd, CarbonImmutable $statutory, CarbonImmutable $due): string
    {
        $note = config('tax.corporation.quarterly_due_days')." days after {$quarterEnd->format('M j')} is {$statutory->format('D, M j')}";

        if ($statutory->equalTo($due)) {
            return "{$note}.";
        }

        for ($day = $statutory; $day->lessThan($due); $day = $day->addDay()) {
            if (($holiday = $this->calendar->holidayName($day)) !== null) {
                $note .= "; {$day->format('M j')} is {$holiday}";
            }
        }

        return "{$note}, so it moves to {$due->format('M j')}.";
    }

    private function quarterStatutoryDate(int $year, int $quarter): CarbonImmutable
    {
        return $this->quarterEnd($year, $quarter)->addDays((int) config('tax.corporation.quarterly_due_days'));
    }

    /**
     * The regular tax on the year's rates, and the MCIT on each quarter's
     * gross income at that quarter's rate (1% until June 2023, then 2%).
     * REVIEW: the MCIT for 2023, split by quarter.
     *
     * @param  list<float>  $grossByQuarter
     * @return Tax
     */
    private function tax(Corporation $corporation, int $year, float $taxableIncome, array $grossByQuarter): array
    {
        $rules = $this->rules->forYear($year);
        $small = $taxableIncome <= (float) config('tax.corporation.small_taxable_income')
            && (float) $corporation->total_assets <= (float) config('tax.corporation.small_total_assets');
        $rate = $rules->rate($small ? 'corporation.small_rate' : 'corporation.rate');
        $regular = round(max(0.0, $taxableIncome) * $rate, 2);
        $mcitApplies = $year >= $this->mcitFrom($corporation);
        $mcit = 0.0;

        foreach ($mcitApplies ? $grossByQuarter : [] as $i => $gross) {
            $mcit += $gross * $this->rules->forQuarter($year, $i + 1)->rate('corporation.mcit_rate');
        }

        $mcit = round(max(0.0, $mcit), 2);

        return [
            'rate' => $rate,
            'small' => $small,
            'regular_tax' => $regular,
            'mcit_applies' => $mcitApplies,
            'mcit' => $mcit,
            'uses' => $mcit > $regular ? 'mcit' : 'regular',
            'tax_due' => max($regular, $mcit),
        ];
    }

    /**
     * One quarter's figures, with the 40% optional standard deduction in
     * place of the books' deductions when the corporation chose it.
     *
     * @return Figures
     */
    private function figures(?CorporationQuarter $row, Corporation $corporation): array
    {
        $sales = (float) ($row->sales ?? 0);
        $cost = (float) ($row->cost_of_sales ?? 0);
        $other = (float) ($row->other_income ?? 0);
        $grossIncome = round($sales - $cost + $other, 2);
        $deductions = $corporation->deduction_method === 'osd'
            ? round(max(0.0, $grossIncome) * (float) config('tax.corporation.osd_rate'), 2)
            : (float) ($row->deductions ?? 0);

        return [
            'sales' => $sales,
            'cost_of_sales' => $cost,
            'gross_from_operation' => round($sales - $cost, 2),
            'other_income' => $other,
            'gross_income' => $grossIncome,
            'deductions' => $deductions,
            'taxable_income' => round($grossIncome - $deductions, 2),
        ];
    }

    /**
     * @param  Figures  $a
     * @param  Figures  $b
     * @return Figures
     */
    private function add(array $a, array $b): array
    {
        foreach ($a as $key => $amount) {
            $a[$key] = round($amount + $b[$key], 2);
        }

        return $a;
    }

    /**
     * @return Collection<int, CorporationQuarter>
     */
    private function rows(Corporation $corporation, int $year): Collection
    {
        return $corporation->quarters()->where('year', $year)->get()->keyBy('quarter');
    }
}
