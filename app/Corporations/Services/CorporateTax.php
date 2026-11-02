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
 * that's higher, declared cumulatively on the 1702Q each quarter. This
 * gathers each year's inputs; CorporateCalculator computes from them.
 *
 * Earlier quarters count as paid with what was recorded when they were
 * marked filed, or else with what they came to.
 *
 * The annual return also carries earlier years forward: a net operating
 * loss (NOLCO) is deducted from the next three years' taxable income (five
 * for 2020 and 2021 losses), and MCIT paid over the regular tax is credited
 * against the next three years' regular tax (NIRC Secs. 27(E), 34(D)(3);
 * RR 25-2020). Neither is claimed on the 1702Q (RR 12-2007).
 *
 * @phpstan-type Carry array{kind: 'nolco'|'excess_mcit', year: int, available: float, applied: float, last_year: int}
 * @phpstan-type Ledger list<array{kind: 'nolco'|'excess_mcit', year: int, remaining: float, last_year: int}>
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
 *     nolco: float,
 *     net_taxable_income: float,
 *     excess_mcit_applied: float,
 *     carried: list<Carry>,
 * }
 */
class CorporateTax
{
    public function __construct(private FilingCalendar $calendar, private TaxRules $rules, private CorporateCalculator $calculator) {}

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
        return $this->calculator->quarters($this->inputs($corporation, $year), $upTo);
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
        if ($this->rows($corporation, $year)->isEmpty()) {
            return null;
        }

        $ledger = [];
        $first = (int) $corporation->quarters()->min('year');

        // The earlier years in turn, each leaving its loss or excess MCIT to the next.
        for ($y = $first; $y < $year; $y++) {
            if ($this->rows($corporation, $y)->isNotEmpty()) {
                $ledger = $this->calculator->year($this->inputs($corporation, $y), $ledger)['ledger'];
            }
        }

        return $this->calculator->year($this->inputs($corporation, $year), $ledger)['annual'];
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

    /**
     * The MCIT rate over the first months of a year, as the months' average:
     * in 2023, 1% for January to June and 2% after, so 1.5% for the year
     * (RMC 69-2023). RMC 36-2024 gives 1.50% for a year ending December 31,
     * 2023 (as Grant Thornton reports it). REVIEW: its text wasn't read.
     */
    public function mcitAverageRate(int $year, int $months = 12): float
    {
        $rate = 0.0;

        for ($month = 1; $month <= $months; $month++) {
            $rate += $this->rules->on(CarbonImmutable::create($year, $month))->rate('corporation.mcit_rate');
        }

        return $rate / $months;
    }

    /**
     * The year MCIT first applies: the 4th taxable year after the year the
     * corporation registered with the BIR (NIRC Sec. 27(E)(1); RR 9-98, as
     * RMC 62-2021 reads it).
     */
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
     * A year's inputs for the calculator: its quarters as entered, the
     * corporation's method and assets, the year's rates by month, the due
     * dates and the 1702-RT's.
     */
    private function inputs(Corporation $corporation, int $year): CorporateInputs
    {
        $dates = [];

        foreach ([1, 2, 3] as $q) {
            $statutory = $this->quarterStatutoryDate($year, $q);
            $due = $this->calendar->nextWorkingDay($statutory);
            $dates[$q] = ['statutory' => $statutory, 'due' => $due, 'due_note' => $this->dueNote($this->quarterEnd($year, $q), $statutory, $due), 'period_end' => $this->quarterEnd($year, $q)];
        }

        $dates[4] = ['statutory' => $this->calendar->incomeTaxStatutoryDate($year, 4), 'due' => $this->calendar->incomeTaxDue($year, 4), 'due_note' => '', 'period_end' => $this->quarterEnd($year, 4)];
        $rules = $this->rules->forYear($year);

        return new CorporateInputs(
            year: $year,
            today: CarbonImmutable::now(),
            deductionMethod: $corporation->deduction_method,
            totalAssets: (float) $corporation->total_assets,
            mcitApplies: $year >= $this->mcitFrom($corporation),
            rows: $this->rows($corporation, $year)->map(fn (CorporationQuarter $row) => [
                'sales' => (float) $row->sales,
                'cost_of_sales' => (float) $row->cost_of_sales,
                'other_income' => (float) $row->other_income,
                'deductions' => (float) $row->deductions,
                'withheld' => (float) $row->withheld,
                'filed_on' => $row->filed_on?->toDateString(),
                'reference_number' => $row->reference_number,
                'amount_paid' => $row->amount_paid !== null ? (float) $row->amount_paid : null,
            ])->all(),
            rules: [
                'rate' => $rules->rate('corporation.rate'),
                'small_rate' => $rules->rate('corporation.small_rate'),
                'mcit_rates' => array_combine(range(1, 12), array_map(fn (int $m) => $this->rules->on(CarbonImmutable::create($year, $m))->rate('corporation.mcit_rate'), range(1, 12))),
                'small_taxable_income' => (float) config('tax.corporation.small_taxable_income'),
                'small_total_assets' => (float) config('tax.corporation.small_total_assets'),
                'osd_rate' => (float) config('tax.corporation.osd_rate'),
            ],
            dates: $dates,
        );
    }

    /**
     * @return Collection<int, CorporationQuarter>
     */
    private function rows(Corporation $corporation, int $year): Collection
    {
        return $corporation->quarters()->where('year', $year)->get()->keyBy('quarter');
    }
}
