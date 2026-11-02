<?php

namespace App\Returns\Forms\Items;

use App\Payroll\Models\Employee;
use App\Payroll\Services\Payroll;
use App\Profiles\Models\TaxProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * BIR Form 2316 the taxpayer gives an employee: the year's pay, the parts
 * left untaxed, and the tax withheld after the year-end adjustment. Basic
 * pay after contributions is exempt (item 29) for a minimum wage earner
 * and for pay of ₱250,000 or less for the year, otherwise taxable (39);
 * contributions are on their own line (36), so item 29 leaves them out.
 * Item 29 is "Basic Salary (including the exempt P250,000 & below) or the
 * Statutory Minimum Wage of the MWE" (2316, September 2021).
 *
 * @phpstan-import-type Values from FormItems
 * @phpstan-import-type EmployeeYear from Payroll
 */
class Form2316Items extends ProfileFormItems
{
    /**
     * @param  EmployeeYear  $pay
     * @return Values
     */
    public function values(TaxProfile $profile, Employee $employee, int $year, array $pay): array
    {
        [$tin1, $tin2, $tin3] = $this->tin($profile);
        $digits = (string) preg_replace('/\D/', '', (string) $employee->tin);
        $first = $pay['months'][0]['month'] ?? 1;
        $last = $pay['months'] === [] ? 12 : end($pay['months'])['month'];
        $from = $employee->started_on->year === $year && $employee->started_on->month === $first
            ? $employee->started_on : CarbonImmutable::create($year, $first);
        $to = $employee->ended_on !== null && $employee->ended_on->year === $year && $employee->ended_on->month === $last
            ? $employee->ended_on : CarbonImmutable::create($year, $last)->endOfMonth();
        $withheld = round($pay['withheld'] - $pay['refund'], 2);
        $basic = round($pay['basic'] - $pay['contributions'], 2);
        $exemptBasic = $pay['minimum_wage'] || (float) config('tax.withholding.compensation_exempt') >= $pay['previous_pay'] + $pay['taxable'];
        $exempt = round(($exemptBasic ? $basic : 0.0) + $pay['benefits_exempt'] + $pay['de_minimis'] + $pay['contributions'], 2);
        $taxable = round($pay['pay'] - $exempt, 2);
        $previousWithheld = $pay['previous_withheld'];

        return [
            '1' => (string) $year,
            '2.from' => $from->format('m/d'),
            '2.to' => $to->format('m/d'),
            '3.1' => substr($digits, 0, 3) ?: null,
            '3.2' => substr($digits, 3, 3) ?: null,
            '3.3' => substr($digits, 6, 3) ?: null,
            '3.branch' => $digits !== '' ? str_pad(substr($digits, 9, 5), 5, '0', STR_PAD_LEFT) : null,
            '4' => Str::upper(Str::squish("{$employee->last_name}, {$employee->first_name} {$employee->middle_name}")),
            '12.1' => $tin1,
            '12.2' => $tin2,
            '12.3' => $tin3,
            '12.branch' => '00000',
            '13' => $this->name($profile),
            '14' => $this->upper($profile->registered_address),
            '14A' => $profile->zip_code,
            '10' => $pay['minimum_wage'] ? number_format((float) $employee->monthly_pay, 2) : null,
            '11' => $pay['minimum_wage'],
            '15.main' => true,
            '19' => $pay['pay'],
            '20' => $exempt,
            '21' => $taxable,
            '22' => $pay['previous_pay'] > 0 ? $pay['previous_pay'] : null,
            '23' => round($taxable + $pay['previous_pay'], 2),
            '24' => $pay['tax'],
            '25A' => $withheld,
            '25B' => $pay['previous_pay'] > 0 ? $previousWithheld : null,
            '26' => round($withheld + $previousWithheld, 2),
            '28' => round($withheld + $previousWithheld, 2),
            '29' => $exemptBasic ? $basic : 0.0,
            '34' => $pay['benefits_exempt'],
            '35' => $pay['de_minimis'],
            '36' => $pay['contributions'],
            '38' => $exempt,
            '39' => $exemptBasic ? 0.0 : $basic,
            '48' => round($pay['benefits'] - $pay['benefits_exempt'], 2),
            '52' => $taxable,
        ];
    }
}
