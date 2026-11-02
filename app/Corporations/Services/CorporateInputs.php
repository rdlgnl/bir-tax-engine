<?php

namespace App\Corporations\Services;

use Carbon\CarbonImmutable;

/**
 * Everything a corporation's year is computed from, gathered beforehand so
 * the calculation reads nothing else: its quarters' figures as entered,
 * its deduction method and assets, whether MCIT applies, the year's rates
 * and the due dates.
 *
 * @phpstan-type Row array{sales: float, cost_of_sales: float, other_income: float, deductions: float, withheld: float, filed_on: string|null, reference_number: string|null, amount_paid: float|null}
 * @phpstan-type CorporateRules array{rate: float, small_rate: float, mcit_rates: array<int, float>, small_taxable_income: float, small_total_assets: float, osd_rate: float}
 * @phpstan-type Dates array{statutory: CarbonImmutable, due: CarbonImmutable, due_note: string, period_end: CarbonImmutable}
 */
final readonly class CorporateInputs
{
    /**
     * @param  'itemized'|'osd'  $deductionMethod
     * @param  array<int, Row>  $rows  by quarter, only those entered
     * @param  CorporateRules  $rules  mcit_rates by month
     * @param  array<int, Dates>  $dates  by quarter; the 4th is the 1702-RT's
     */
    public function __construct(
        public int $year,
        public CarbonImmutable $today,
        public string $deductionMethod,
        public float $totalAssets,
        public bool $mcitApplies,
        public array $rows,
        public array $rules,
        public array $dates,
    ) {}
}
