<?php

namespace App\Tax\Calculation;

use App\Tax\Enums\TaxpayerClassification;
use Carbon\CarbonImmutable;

/**
 * Everything a year's tax is computed from, gathered beforehand so the
 * calculation itself reads nothing else: no database, no config. Amounts
 * are in centavos.
 *
 * Each sale carries the day it counts for income tax (on the books' method
 * of the year it's dated) and for percentage tax and VAT, either null when
 * it doesn't count yet.
 *
 * @phpstan-import-type SalaryInput from \App\Tax\Services\Compensation
 *
 * @phpstan-type Sale array{amount: int, withheld: int, confirmed: bool, income_date: CarbonImmutable|null, sales_tax_date: CarbonImmutable|null}
 * @phpstan-type Expense array{date: CarbonImmutable, deductible: int}
 * @phpstan-type QuarterFiling array{filed: bool, filed_on: string|null, paid_on: string|null, paid: int|null}
 * @phpstan-type Rules array{brackets: list<array{int|float, int|float, float}>, percentage_rates: array<int, float>, eight_percent_rate: float, exempt_amount: int, gross_limit: int, osd_rate: float, classes: array{micro: float, small: float, medium: float}}
 * @phpstan-type QuarterDates array{statutory: CarbonImmutable, due: CarbonImmutable, moved_reason: string|null}
 */
final readonly class TaxInputs
{
    /**
     * @param  list<Sale>  $sales
     * @param  list<Expense>  $expenses
     * @param  array<int, QuarterFiling>  $filings  by quarter
     * @param  Rules  $rules
     * @param  array<int, QuarterDates>  $dates  by quarter
     * @param  SalaryInput|null  $salary  a salary alongside the business, for the annual return
     */
    public function __construct(
        public int $year,
        public CarbonImmutable $today,
        public YearTaxpayer $taxpayer,
        public array $sales,
        public array $expenses,
        public array $filings,
        public array $rules,
        public array $dates,
        public ?TaxpayerClassification $assignedClass,
        public ?array $salary,
    ) {}
}
