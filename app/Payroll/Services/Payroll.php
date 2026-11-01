<?php

namespace App\Payroll\Services;

use App\Payroll\Models\Employee;
use App\Returns\Models\Filing;
use App\Tax\Services\FilingCalendar;
use App\Tax\Services\TaxCalculationService;
use Carbon\CarbonImmutable;

/**
 * Tax withheld on salaries: each month the tax on the pay as if earned all
 * year (the graduated rates on twelve times the month's pay, a twelfth of
 * it), and in the last month worked in the year the adjustment that makes
 * the year's withholding equal the tax on the year's pay. What was withheld
 * too much is refunded to the employee by January 25.
 *
 * @phpstan-type Status 'open'|'due'|'overdue'|'filed'
 * @phpstan-type PayMonth array{month: int, pay: float, withheld: float, refund: float, year_end: bool}
 * @phpstan-type EmployeeYear array{months: list<PayMonth>, pay: float, tax: float, withheld: float, refund: float}
 * @phpstan-type RemittanceMonth array{month: int, employees: int, compensation: float, withheld: float, refund: float, due_date: string, status: Status, filed_on: string|null}
 */
class Payroll
{
    public function __construct(
        private TaxCalculationService $engine,
        private FilingCalendar $calendar,
    ) {}

    /**
     * Tax withheld on one month's pay, before the year-end adjustment.
     */
    public function monthlyWithholding(float $pay): float
    {
        return round($this->engine->graduatedTax($pay * 12) / 12, 2);
    }

    /**
     * An employee's year, month by month, with the year-end adjustment.
     *
     * @return EmployeeYear
     */
    public function employeeYear(Employee $employee, int $year): array
    {
        $worked = array_values(array_filter(range(1, 12), fn (int $m) => $employee->workedIn($year, $m)));
        $pay = (float) $employee->monthly_pay;
        $annualTax = $this->engine->graduatedTax($pay * count($worked));
        $months = [];
        $withheld = 0.0;
        $refund = 0.0;

        foreach ($worked as $i => $month) {
            $last = $i === count($worked) - 1;
            $tax = $this->monthlyWithholding($pay);

            if ($last) {
                // The year-end adjustment: withhold what's still owed, or refund the excess.
                $tax = round(max(0.0, $annualTax - $withheld), 2);
                $refund = round(max(0.0, $withheld - $annualTax), 2);
            }

            $withheld += $tax;
            $months[] = ['month' => $month, 'pay' => $pay, 'withheld' => $tax, 'refund' => $last ? $refund : 0.0, 'year_end' => $last];
        }

        return [
            'months' => $months,
            'pay' => round($pay * count($worked), 2),
            'tax' => $annualTax,
            'withheld' => round($withheld, 2),
            'refund' => $refund,
        ];
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
            $rows = $years->map(fn (array $y) => collect($y['months'])->firstWhere('month', $month))->filter();

            if ($rows->isEmpty()) {
                continue;
            }

            $due = $this->calendar->withholdingMonthlyDue($year, $month);
            $filing = $filings->first(fn (Filing $f) => $f->month === $month);
            $months[] = [
                'month' => $month,
                'employees' => $rows->count(),
                'compensation' => round((float) $rows->sum('pay'), 2),
                'withheld' => round((float) $rows->sum('withheld'), 2),
                'refund' => round((float) $rows->sum('refund'), 2),
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
