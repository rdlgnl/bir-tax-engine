<?php

namespace App\Returns\Forms\Items;

use App\Payroll\Models\Employee;
use App\Payroll\Services\Payroll;
use App\Profiles\Models\TaxProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * BIR Form 2316 the taxpayer gives an employee: the year's pay and the tax
 * withheld on it after the year-end adjustment. The pay recorded is
 * taxable, so it goes in as basic salary with no exempt part.
 * REVIEW: pay of ₱250,000 or less for the year may belong in item 29
 * (exempt basic salary) instead.
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
            '15.main' => true,
            '19' => $pay['pay'],
            '20' => 0.0,
            '21' => $pay['pay'],
            '23' => $pay['pay'],
            '24' => $pay['tax'],
            '25A' => $withheld,
            '26' => $withheld,
            '28' => $withheld,
            '39' => $pay['pay'],
            '52' => $pay['pay'],
        ];
    }
}
