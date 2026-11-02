<?php

namespace App\Tax\Calculation;

use App\Profiles\Enums\IncomeSource;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Enums\Regime;
use App\Tax\Enums\TaxpayerClassification;
use App\Tax\Services\TaxCalculationService;
use Carbon\CarbonImmutable;

/**
 * A year's income tax, quarter by quarter, from its inputs alone: no
 * database and no config, so the same inputs always give the same result.
 * TaxCalculationService gathers the inputs.
 *
 * Quarterly returns are cumulative: each one computes tax on the year so
 * far, then subtracts what earlier quarters paid and what clients withheld.
 * Quarter 4 is the annual return. Internally everything is in integer
 * centavos so sums never drift; results are in pesos.
 *
 * Each quarter carries its trace: every step from gross to the amount
 * due, with the rule it applies, its rate and the bracket used. Amounts
 * round to the centavo, half up, at each multiplication.
 *
 * @phpstan-import-type YearResult from TaxCalculationService
 * @phpstan-import-type Switch from TaxCalculationService
 * @phpstan-import-type Step from TaxCalculationService
 * @phpstan-import-type Sale from TaxInputs
 * @phpstan-import-type Rules from TaxInputs
 */
class TaxCalculator
{
    /**
     * @return YearResult
     */
    public function year(TaxInputs $in): array
    {
        $year = $in->year;
        $today = $in->today->startOfDay();
        $profile = $in->taxpayer;
        $rules = $in->rules;
        $income = array_values(array_filter($in->sales, fn (array $s) => $s['income_date']?->year === $year));
        usort($income, fn (array $a, array $b) => strcmp($a['income_date']->toDateString(), $b['income_date']->toDateString()));
        $sales = array_values(array_filter($in->sales, fn (array $s) => $s['sales_tax_date']?->year === $year));
        $yearGross = $this->sum($income, 'amount');

        $status = $profile->incomeSource === IncomeSource::Employed ? 'not_applicable' : 'ok';
        $switched = $this->switchToGraduated($profile, $year, $income, $rules);
        $classification = $in->assignedClass ?? TaxpayerClassification::fromGrossUnder($this->pesos($yearGross), $rules['classes']);

        $quarters = [];
        $priorPaid = 0;
        // Earlier quarters' tax that wasn't paid and isn't credited: it's still owed.
        $unpaidEarlier = 0;

        foreach ([1, 2, 3, 4] as $quarter) {
            $toDate = array_filter($income, fn (array $s) => $s['income_date']->quarter <= $quarter);
            $inQuarter = array_filter($income, fn (array $s) => $s['income_date']->quarter === $quarter);

            $gross = $this->sum($toDate, 'amount');
            $deductible = $this->sum(array_filter($in->expenses, fn (array $e) => $e['date']->quarter <= $quarter), 'deductible');
            // The tax a client withheld counts with the income it was withheld from.
            $withheld = $this->sum($toDate, 'withheld');
            $unconfirmed = $this->sum(array_filter($toDate, fn (array $s) => ! $s['confirmed']), 'withheld');
            $quarterGross = $this->sum($inQuarter, 'amount');
            $quarterSales = array_filter($sales, fn (array $s) => $s['sales_tax_date']->quarter === $quarter);
            // Graduated rates from the quarter an 8% year passed ₱3,000,000.
            $taxpayer = $switched !== null && $quarter >= $switched['quarter'] ? $profile->onGraduatedRates() : $profile;

            $tax = $this->incomeTax($taxpayer, $gross, $deductible, $rules);
            $compensation = null;
            $salaryTax = 0;
            $salaryWithheld = 0;
            $salary = $in->salary;

            // The annual return adds a salary: taxed at graduated rates, with
            // business income on graduated rates stacked on top of it, and
            // what the employer withheld credited.
            if ($quarter === 4 && $salary !== null && $taxpayer->regime !== null) {
                $salaryTaxable = max(0, $this->cents($salary['gross']) - $this->cents($salary['non_taxable']));
                $salaryTax = $this->graduated($salaryTaxable, $rules['brackets']);
                $salaryWithheld = $salary['withheld'] === null ? $salaryTax : $this->cents($salary['withheld']);

                if ($taxpayer->regime === Regime::Graduated) {
                    $tax['tax'] = $this->stackedGraduated($salaryTaxable, $tax['taxable'], $rules['brackets']);
                }

                $compensation = [
                    'employer' => $salary['employer'],
                    'employer_tin' => $salary['employer_tin'],
                    'gross' => $salary['gross'],
                    'non_taxable' => $salary['non_taxable'],
                    'taxable' => $this->pesos($salaryTaxable),
                    'tax' => $this->pesos($salaryTax),
                    'withheld' => $this->pesos($salaryWithheld),
                    'source' => $salary['source'],
                ];
            }

            $due = max(0, $tax['tax'] + $salaryTax - $withheld - $salaryWithheld - $priorPaid);

            // A year on two methods files the 1701, not the 1701-MS (1701-MS guidelines).
            $form = $switched !== null && $quarter === 4 ? '1701' : $taxpayer->incomeTaxForm($quarter, $classification);
            $filing = $in->filings[$quarter] ?? null;
            // The tax paid for the quarter, on the original return and any amendment.
            $quarterPaid = $filing['paid'] ?? null;
            $filed = $filing !== null && $filing['filed'];
            $dueDate = $in->dates[$quarter]['due'];
            $periodEnd = CarbonImmutable::create($year, $quarter * 3)->endOfMonth()->startOfDay();

            $quarterStatus = match (true) {
                $filed => 'filed',
                $today->lessThanOrEqualTo($periodEnd) => 'open',
                $today->greaterThan($dueDate) => 'overdue',
                default => 'due',
            };

            $percentage = $this->quarterPercentageTax($taxpayer, $quarter, $quarterSales, $sales, $switched, $rules);
            $quarters[$quarter] = [
                'quarter' => $quarter,
                'form' => $form,
                'regime' => $taxpayer->regime?->value,
                // Only graduated rates deduct anything.
                'deduction_method' => $taxpayer->regime === Regime::Graduated ? $taxpayer->deductionMethod?->value : null,
                'quarter_gross' => $this->pesos($quarterGross),
                'quarter_withheld' => $this->pesos($this->sum($inQuarter, 'withheld')),
                'gross' => $this->pesos($gross),
                'deductible_expenses' => $this->pesos($deductible),
                'exempt_amount' => $this->pesos($tax['exempt']),
                'deduction' => $this->pesos($tax['deduction']),
                'taxable' => $this->pesos($tax['taxable']),
                'income_tax' => $this->pesos($tax['tax']),
                'prior_payments' => $this->pesos($priorPaid),
                'unpaid_earlier' => $this->pesos($unpaidEarlier),
                'withheld' => $this->pesos($withheld),
                'withheld_unconfirmed' => $this->pesos($unconfirmed),
                'due' => $this->pesos($due),
                'excess_credit' => $this->pesos(max(0, $withheld + $salaryWithheld + $priorPaid - $tax['tax'] - $salaryTax)),
                ...$percentage,
                'statutory_date' => $in->dates[$quarter]['statutory']->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'due_date_moved_reason' => $in->dates[$quarter]['moved_reason'],
                'days_left' => (int) round($today->diffInDays($dueDate, false)),
                'status' => $quarterStatus,
                'filed_on' => $filing['filed_on'] ?? null,
                'amount_paid' => $quarterPaid !== null ? $this->pesos($quarterPaid) : null,
                'paid_on' => $filing['paid_on'] ?? null,
                'compensation' => $compensation,
                'trace' => $this->trace($taxpayer, $quarter, $rules, $gross, $tax, $compensation, $priorPaid, $withheld, $due, $percentage),
            ];

            // What later quarters subtract: the payment recorded, or, while the
            // quarter can still be paid on time, the tax it owes. One overdue,
            // or filed with nothing paid, isn't credited: its tax stays in the
            // later returns, as the 1701Q's "tax paid in previous quarters" has it.
            $credited = match (true) {
                $quarterPaid !== null => $quarterPaid,
                ! $filed && $quarterStatus !== 'overdue' => $due,
                default => 0,
            };
            $priorPaid += $credited;
            $unpaidEarlier += $quarterPaid === null ? $due - $credited : max(0, $due - $quarterPaid);
        }

        return [
            'year' => $year,
            'regime' => $profile->regime?->value,
            'deduction_method' => $profile->deductionMethod?->value,
            'status' => $status,
            'gross' => $this->pesos($yearGross),
            'switched' => $switched,
            'classification' => $classification->value,
            'quarters' => $quarters,
        ];
    }

    /**
     * The steps from the year's gross to the amount due, and the quarter's
     * percentage tax, each with the rule it applies.
     *
     * @param  Rules  $rules
     * @param  array{exempt: int, deduction: int, taxable: int, tax: int}  $tax
     * @param  array{taxable: float, tax: float, withheld: float}|array<string, mixed>|null  $salary
     * @param  array{percentage_tax: float|null, percentage_tax_base: float|null}  $percentage
     * @return list<Step>
     */
    private function trace(YearTaxpayer $taxpayer, int $quarter, array $rules, int $gross, array $tax, ?array $salary, int $priorPaid, int $withheld, int $due, array $percentage): array
    {
        $ids = $rules['rule_ids'] ?? ['graduated' => null, 'percentage_tax' => []];
        $step = $this->step(...);
        $steps = [$step('gross', $quarter === 4 ? 'Gross sales for the year' : 'Gross sales to date', $gross)];

        if ($taxpayer->regime === Regime::EightPercent) {
            $rule = $taxpayer->purelySelfEmployed() ? 'IT-8PCT-PURE' : 'IT-8PCT-MIXED';

            if ($taxpayer->purelySelfEmployed()) {
                $steps[] = $step('reduction', 'Less: the ₱250,000 reduction', $tax['exempt'], $rule);
            }

            $steps[] = $step('taxable', 'Taxable', $tax['taxable']);
            $steps[] = $step('income_tax', 'Income tax at 8%', $tax['tax'], $rule, $rules['eight_percent_rate']);
        } elseif ($taxpayer->regime === Regime::Graduated) {
            $steps[] = $taxpayer->deductionMethod === DeductionMethod::Itemized
                ? $step('deduction', 'Less: expenses with invoices', $tax['deduction'], 'IT-ITEMIZED')
                : $step('deduction', 'Less: the 40% standard deduction', $tax['deduction'], 'IT-OSD', $rules['osd_rate']);
            $steps[] = $step('taxable', 'Taxable', $tax['taxable']);
            $steps[] = $step('income_tax', 'Income tax, graduated rates', $tax['tax'], $ids['graduated'], null, $this->bracket($tax['taxable'], $rules['brackets']));
        }

        if ($salary !== null) {
            $steps[] = $step('salary_tax', 'Tax on the salary', (float) $salary['tax'], $ids['graduated'], null, 'On ₱'.number_format((float) $salary['taxable'], 2).' of taxable salary');
            $steps[] = $step('salary_withheld', "Less: the employer's withholding", (float) $salary['withheld']);
        }

        $steps[] = $step('prior_payments', 'Less: paid in earlier quarters', $priorPaid);
        $steps[] = $step('withheld', 'Less: creditable tax withheld by clients', $withheld, $withheld > 0 ? 'CWT-CREDIT' : null);
        $steps[] = $step('due', 'Tax due', $due);

        if ($percentage['percentage_tax'] !== null) {
            $steps[] = $step('percentage_tax', 'Percentage tax', $percentage['percentage_tax'], $ids['percentage_tax'][$quarter] ?? null, $rules['percentage_rates'][$quarter], 'On ₱'.number_format((float) $percentage['percentage_tax_base'], 2).' of sales', 'percentage_tax');
        }

        return $steps;
    }

    /**
     * One step of the trace; an amount in centavos as an int, in pesos as a float.
     *
     * @param  'income_tax'|'percentage_tax'  $part
     * @return Step
     */
    private function step(string $key, string $label, int|float $amount, ?string $rule = null, ?float $rate = null, ?string $note = null, string $part = 'income_tax'): array
    {
        return ['part' => $part, 'step' => $key, 'label' => $label, 'amount' => is_int($amount) ? $this->pesos($amount) : $amount, 'rule' => $rule, 'rate' => $rate, 'note' => $note];
    }

    /**
     * The graduated table's band a taxable amount falls in, in words.
     *
     * @param  list<array{int|float, int|float, float}>  $brackets
     */
    private function bracket(int $taxable, array $brackets): string
    {
        $band = $brackets[0];

        foreach ($brackets as $bracket) {
            if ($taxable > $this->cents($bracket[0])) {
                $band = $bracket;
            }
        }

        [$over, $base, $rate] = $band;

        return $rate <= 0
            ? 'Up to ₱'.number_format((float) $brackets[1][0]).': no tax'
            : ($base > 0 ? '₱'.number_format((float) $base).' + ' : '').round($rate * 100).'% of the excess over ₱'.number_format((float) $over);
    }

    /**
     * Income tax on a gross and deductible amount to date, in centavos.
     *
     * @param  Rules  $rules
     * @return array{exempt: int, deduction: int, taxable: int, tax: int}
     */
    public function incomeTax(YearTaxpayer $taxpayer, int $gross, int $deductible, array $rules): array
    {
        if ($taxpayer->incomeSource === IncomeSource::Employed || $taxpayer->regime === null) {
            return ['exempt' => 0, 'deduction' => 0, 'taxable' => 0, 'tax' => 0];
        }

        if ($taxpayer->regime === Regime::EightPercent) {
            // Only the purely self-employed get the ₱250,000 reduction.
            $exempt = $taxpayer->purelySelfEmployed() ? min($gross, $rules['exempt_amount']) : 0;
            $taxable = $gross - $exempt;

            return ['exempt' => $exempt, 'deduction' => 0, 'taxable' => $taxable, 'tax' => $this->rate($taxable, $rules['eight_percent_rate'])];
        }

        $deduction = $taxpayer->deductionMethod === DeductionMethod::Itemized
            ? min($gross, $deductible)
            : $this->rate($gross, $rules['osd_rate']);
        $taxable = $gross - $deduction;

        return ['exempt' => 0, 'deduction' => $deduction, 'taxable' => $taxable, 'tax' => $this->graduated($taxable, $rules['brackets'])];
    }

    /**
     * @param  list<array{int|float, int|float, float}>  $brackets
     */
    public function graduated(int $taxable, array $brackets): int
    {
        $tax = 0;

        foreach ($brackets as [$over, $base, $rate]) {
            $overCents = $this->cents($over);

            if ($taxable > $overCents) {
                $tax = $this->cents($base) + $this->rate($taxable - $overCents, $rate);
            }
        }

        return $tax;
    }

    /**
     * Graduated tax on business income stacked on top of a salary, so the
     * business is taxed at the salary's top rate onward.
     *
     * @param  list<array{int|float, int|float, float}>  $brackets
     */
    public function stackedGraduated(int $salary, int $taxable, array $brackets): int
    {
        return $this->graduated($salary + $taxable, $brackets) - $this->graduated($salary, $brackets);
    }

    public function rate(int $cents, float $rate): int
    {
        return (int) round($cents * $rate);
    }

    public function cents(string|int|float|null $pesos): int
    {
        return (int) round((float) $pesos * 100);
    }

    public function pesos(int $cents): float
    {
        return round($cents / 100, 2);
    }

    /**
     * An 8% year whose sales pass ₱3,000,000 is on graduated rates from
     * then on, with the 8% paid before credited (RR 8-2018). The
     * registration is updated within the next month, VAT applies from the
     * first day of that month, and percentage tax is owed on the sales up
     * to it. The limit is "exceeded": exactly ₱3,000,000 stays on 8%.
     *
     * @param  list<Sale>  $income  in the order they count
     * @param  Rules  $rules
     * @return Switch|null
     */
    private function switchToGraduated(YearTaxpayer $taxpayer, int $year, array $income, array $rules): ?array
    {
        if ($taxpayer->regime !== Regime::EightPercent) {
            return null;
        }

        $total = 0;

        foreach ($income as $sale) {
            $total += $sale['amount'];
            $on = $sale['income_date'];

            if ($on !== null && $total > $rules['gross_limit']) {
                $next = CarbonImmutable::create($year, $on->month)->addMonth();

                return [
                    'month' => $on->month,
                    'quarter' => $on->quarter,
                    'vat_from' => $next->toDateString(),
                    'register_by' => $next->endOfMonth()->toDateString(),
                ];
            }
        }

        return null;
    }

    /**
     * A quarter's percentage tax and the sales it's on, each sale at its own
     * quarter's rate. In a year that left 8%, it's all on the 2551Q of the
     * quarter the limit was passed: the sales from January to that month,
     * payable then without penalty; VAT replaces it after.
     *
     * @param  array<int, Sale>  $quarterSales
     * @param  list<Sale>  $sales  the year's
     * @param  Switch|null  $switched
     * @param  Rules  $rules
     * @return array{percentage_tax: float|null, percentage_tax_base: float|null}
     */
    private function quarterPercentageTax(YearTaxpayer $taxpayer, int $quarter, array $quarterSales, array $sales, ?array $switched, array $rules): array
    {
        $none = ['percentage_tax' => null, 'percentage_tax_base' => null];

        if ($switched === null) {
            $base = $this->sum($quarterSales, 'amount');

            return $taxpayer->owesPercentageTax()
                ? ['percentage_tax' => $this->pesos($this->rate($base, $rules['percentage_rates'][$quarter])), 'percentage_tax_base' => $this->pesos($base)]
                : $none;
        }

        if ($quarter !== $switched['quarter']) {
            return $none;
        }

        $toSwitch = array_filter($sales, fn (array $s) => $s['sales_tax_date']?->month <= $switched['month']);
        $tax = array_sum(array_map(fn (array $s) => $this->rate($s['amount'], $rules['percentage_rates'][$s['sales_tax_date']->quarter]), $toSwitch));

        return ['percentage_tax' => $this->pesos($tax), 'percentage_tax_base' => $this->pesos($this->sum($toSwitch, 'amount'))];
    }

    /**
     * @param  array<array-key, array<string, mixed>>  $rows
     */
    private function sum(array $rows, string $field): int
    {
        return (int) array_sum(array_map(fn (array $row) => (int) $row[$field], $rows));
    }
}
