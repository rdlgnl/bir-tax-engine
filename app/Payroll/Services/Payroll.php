<?php

namespace App\Payroll\Services;

use App\Payroll\Models\Employee;
use App\Payroll\Models\EmployeeCertificate;
use App\Returns\Models\Filing;
use App\Tax\Services\FilingCalendar;
use App\Tax\Services\TaxCalculationService;
use App\Tax\Services\TaxRules;
use Carbon\CarbonImmutable;

/**
 * Tax withheld on salaries (RR 2-98 Sec. 2.79 as RR 11-2018 amended it).
 * Each month the BIR table for the month's taxable pay; for someone who
 * joined from another employer this year, the cumulative average method
 * (the table on the average month so far, less what's been withheld). In
 * the last month worked in the year the annualized computation makes the
 * year's withholding equal the tax on the year's taxable pay; what was
 * withheld too much is refunded to the employee by January 25.
 *
 * Taxable pay is basic pay less the employee's share of contributions; a
 * minimum wage earner's isn't taxed. De minimis benefits are paid on top,
 * untaxed. The 13th month (a twelfth of the year's basic pay) is paid with
 * the last month, untaxed up to ₱90,000.
 *
 * @phpstan-type Status 'open'|'due'|'overdue'|'filed'
 * @phpstan-type PayMonth array{month: int, pay: float, benefits: float, de_minimis: float, contributions: float, taxable: float, withheld: float, refund: float, year_end: bool}
 * @phpstan-type EmployeeYear array{months: list<PayMonth>, method: 'table'|'cumulative', minimum_wage: bool, pay: float, basic: float, benefits: float, benefits_exempt: float, de_minimis: float, contributions: float, taxable: float, previous_pay: float, previous_withheld: float, tax: float, withheld: float, refund: float}
 * @phpstan-type RemittanceMonth array{month: int, employees: int, compensation: float, minimum_wage: float, benefits: float, de_minimis: float, contributions: float, taxable: float, not_withheld: float, withheld: float, refund: float, due_date: string, status: Status, filed_on: string|null}
 * @phpstan-type AnnualEmployee array{id: int, name: string, tin: string|null, pay: float, taxable: float, tax: float, withheld: float, refund: float, given_on: string|null}
 * @phpstan-type Annual array{year: int, months: list<RemittanceMonth>, employees: list<AnnualEmployee>, compensation: float, withheld: float, refund: float, due_date: string, status: Status, filed_on: string|null}
 */
class Payroll
{
    public function __construct(
        private TaxCalculationService $engine,
        private FilingCalendar $calendar,
        private TaxRules $rules,
    ) {}

    /**
     * Tax withheld on a month's taxable pay by the BIR table in force that
     * month (this month unless said otherwise), before any year-end adjustment.
     */
    public function monthlyWithholding(float $taxable, ?int $year = null, int $month = 1): float
    {
        $on = $year === null ? CarbonImmutable::now() : CarbonImmutable::create($year, $month);
        $tax = 0.0;

        foreach ($this->rules->on($on)->brackets('compensation_brackets') as [$over, $base, $rate]) {
            if ($taxable > $over) {
                $tax = $base + ($taxable - $over) * $rate;
            }
        }

        return round($tax, 2);
    }

    /**
     * An employee's taxable pay for one month: basic pay less their share
     * of contributions, or nothing for a minimum wage earner.
     */
    public function taxablePay(Employee $employee): float
    {
        return $employee->minimum_wage ? 0.0 : round(max(0.0, (float) $employee->monthly_pay - (float) $employee->contributions), 2);
    }

    /**
     * An employee's year, month by month, with the year-end adjustment.
     *
     * @return EmployeeYear
     */
    public function employeeYear(Employee $employee, int $year): array
    {
        $worked = array_values(array_filter(range(1, 12), fn (int $m) => $employee->workedIn($year, $m)));
        $basic = (float) $employee->monthly_pay;
        $contributions = (float) $employee->contributions;
        $deMinimis = (float) $employee->de_minimis;
        $regular = $this->taxablePay($employee);
        $previous = $employee->previousEmployer($year);
        // A new hire who had another employer this year (Sec. 2.79(B)(5)(a)).
        // REVIEW: the periods averaged are the months from January.
        $cumulative = $previous['pay'] > 0;

        $benefits = round($basic * count($worked) / 12, 2);
        $benefitsExempt = min($benefits, (float) config('tax.withholding.benefits_exempt'));
        $taxable = round($regular * count($worked) + $benefits - $benefitsExempt, 2);
        $annualTax = $this->engine->graduatedTax($previous['pay'] + $taxable, $year);
        // The year's tax still owed here once the earlier employer's withholding counts.
        $owed = max(0.0, $annualTax - $previous['withheld']);
        $months = [];
        $withheld = 0.0;
        $refund = 0.0;

        foreach ($worked as $i => $month) {
            $last = $i === count($worked) - 1;
            $monthTaxable = $last ? round($regular + $benefits - $benefitsExempt, 2) : $regular;

            if ($last) {
                // The year-end adjustment: withhold what's still owed, or refund the excess.
                $tax = round(max(0.0, $owed - $withheld), 2);
                $refund = round(max(0.0, $withheld - $owed), 2);
            } elseif ($cumulative) {
                $average = ($previous['pay'] + $regular * ($i + 1)) / $month;
                $tax = round(max(0.0, $this->monthlyWithholding($average, $year, $month) * $month - $previous['withheld'] - $withheld), 2);
            } else {
                $tax = $this->monthlyWithholding($regular, $year, $month);
            }

            $withheld += $tax;
            $months[] = [
                'month' => $month,
                'pay' => $basic,
                'benefits' => $last ? $benefits : 0.0,
                'de_minimis' => $deMinimis,
                'contributions' => $contributions,
                'taxable' => $monthTaxable,
                'withheld' => $tax,
                'refund' => $last ? $refund : 0.0,
                'year_end' => $last,
            ];
        }

        $count = count($worked);

        return [
            'months' => $months,
            'method' => $cumulative ? 'cumulative' : 'table',
            'minimum_wage' => $employee->minimum_wage,
            'pay' => round(($basic + $deMinimis) * $count + $benefits, 2),
            'basic' => round($basic * $count, 2),
            'benefits' => $benefits,
            'benefits_exempt' => $benefitsExempt,
            'de_minimis' => round($deMinimis * $count, 2),
            'contributions' => round($contributions * $count, 2),
            'taxable' => $taxable,
            'previous_pay' => $previous['pay'],
            'previous_withheld' => $previous['withheld'],
            'tax' => $annualTax,
            'withheld' => round($withheld, 2),
            'refund' => $refund,
        ];
    }

    /**
     * The year for the 1604-C: each month's 1601-C, each employee's pay and
     * tax with whether their 2316 was given, and where the return stands.
     * It's due January 31 of the next year.
     *
     * @return Annual
     */
    public function annual(int $year, ?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::now();
        $months = $this->year($year, $today);
        $given = EmployeeCertificate::query()->where('year', $year)->pluck('given_on', 'employee_id');
        $employees = [];

        foreach (Employee::query()->orderBy('last_name')->get() as $employee) {
            $employeeYear = $this->employeeYear($employee, $year);

            if ($employeeYear['months'] === []) {
                continue;
            }

            $givenOn = $given->get($employee->id);
            $employees[] = [
                'id' => $employee->id,
                'name' => $employee->name(),
                'tin' => $employee->tin,
                'pay' => $employeeYear['pay'],
                'taxable' => $employeeYear['taxable'],
                'tax' => $employeeYear['tax'],
                'withheld' => $employeeYear['withheld'],
                'refund' => $employeeYear['refund'],
                'given_on' => $givenOn instanceof CarbonImmutable ? $givenOn->toDateString() : null,
            ];
        }

        $due = $this->annualDue($year);
        $filing = Filing::query()->where('year', $year)->where('form', '1604-C')->first();

        return [
            'year' => $year,
            'months' => $months,
            'employees' => $employees,
            'compensation' => round(array_sum(array_column($months, 'compensation')), 2),
            'withheld' => round(array_sum(array_column($months, 'withheld')), 2),
            'refund' => round(array_sum(array_column($months, 'refund')), 2),
            'due_date' => $due->toDateString(),
            'status' => match (true) {
                $filing !== null => 'filed',
                $today->lessThanOrEqualTo(CarbonImmutable::create($year)->endOfYear()) => 'open',
                $today->greaterThan($due->endOfDay()) => 'overdue',
                default => 'due',
            },
            'filed_on' => $filing?->filed_on->toDateString(),
        ];
    }

    /**
     * The 1604-C, and the 2316 for each employee: January 31 of the next year.
     */
    public function annualDue(int $year): CarbonImmutable
    {
        return $this->calendar->nextWorkingDay(CarbonImmutable::create($year + 1, 1, 31));
    }

    /**
     * The year's 1601-C months: what was paid and withheld across employees,
     * and where each return stands.
     *
     * @return list<RemittanceMonth>
     */
    public function year(int $year, ?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::now();
        $employees = Employee::query()->get();
        $years = $employees->map(fn (Employee $e) => $this->employeeYear($e, $year));
        $filings = Filing::query()->where('year', $year)->where('form', '1601-C')->get();
        $months = [];

        foreach (range(1, 12) as $month) {
            $rows = $years->map(fn (array $y) => [$y, collect($y['months'])->firstWhere('month', $month)])->filter(fn (array $row) => $row[1] !== null);

            if ($rows->isEmpty()) {
                continue;
            }

            $sum = fn (callable $amount) => round((float) $rows->sum(fn (array $row) => $amount(...$row)), 2);

            $due = $this->calendar->compensationMonthlyDue($year, $month);
            $filing = $filings->first(fn (Filing $f) => $f->month === $month);
            $months[] = [
                'month' => $month,
                'employees' => $rows->count(),
                'compensation' => $sum(fn (array $y, array $m) => $m['pay'] + $m['benefits'] + $m['de_minimis']),
                // A minimum wage earner's pay after their contributions, which count on their own line.
                'minimum_wage' => $sum(fn (array $y, array $m) => $y['minimum_wage'] ? $m['pay'] - $m['contributions'] : 0.0),
                'benefits' => $sum(fn (array $y, array $m) => $m['year_end'] ? $y['benefits_exempt'] : 0.0),
                'de_minimis' => $sum(fn (array $y, array $m) => $m['de_minimis']),
                'contributions' => $sum(fn (array $y, array $m) => $m['contributions']),
                'taxable' => $sum(fn (array $y, array $m) => $m['taxable']),
                // Employees other than minimum wage earners whose year's pay is ₱250,000 or less.
                'not_withheld' => $sum(fn (array $y, array $m) => ! $y['minimum_wage'] && (float) config('tax.withholding.compensation_exempt') >= $y['previous_pay'] + $y['taxable'] ? $m['taxable'] : 0.0),
                'withheld' => $sum(fn (array $y, array $m) => $m['withheld']),
                'refund' => $sum(fn (array $y, array $m) => $m['refund']),
                'due_date' => $due->toDateString(),
                'status' => match (true) {
                    $filing !== null => 'filed',
                    $today->lessThanOrEqualTo(CarbonImmutable::create($year, $month)->endOfMonth()) => 'open',
                    $today->greaterThan($due->endOfDay()) => 'overdue',
                    default => 'due',
                },
                'filed_on' => $filing?->filed_on->toDateString(),
            ];
        }

        return $months;
    }
}
