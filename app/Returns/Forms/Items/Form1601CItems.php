<?php

namespace App\Returns\Forms\Items;

use App\Payroll\Services\Payroll;
use App\Profiles\Models\TaxProfile;
use App\Tax\Services\Penalties;
use Carbon\CarbonImmutable;

/**
 * BIR Form 1601-C: a month's tax withheld on salaries. The month's pay, the
 * parts left untaxed (a minimum wage, the 13th month up to ₱90,000, de
 * minimis benefits, the employees' contributions), and the taxable rest.
 * What was withheld is what's remitted.
 *
 * @phpstan-import-type Values from FormItems
 * @phpstan-import-type RemittanceMonth from Payroll
 * @phpstan-import-type Penalty from Penalties
 */
class Form1601CItems extends ProfileFormItems
{
    /**
     * @param  RemittanceMonth  $month
     * @param  Penalty|null  $penalties
     * @return Values
     */
    public function values(TaxProfile $profile, int $year, array $month, ?array $penalties = null): array
    {
        $withheld = $month['withheld'];
        $surcharge = $penalties['surcharge'] ?? 0.0;
        $interest = $penalties['interest'] ?? 0.0;

        return $this->identity($profile, [
            'tin' => ['tin.1', 'tin.2', 'tin.3'],
            'rdo' => '7',
            'name' => '8',
            'address' => ['9.1', 40, '9.2'],
            'zip' => '9A',
            'contact' => '10',
            'email' => '12',
        ]) + [
            '1' => CarbonImmutable::create($year, $month['month'])->format('mY'),
            '2.no' => true,
            '3.yes' => $withheld > 0,
            '3.no' => $withheld <= 0,
            'tin.branch' => '00000',
            '11.private' => true,
            '13.no' => true,
            '14' => $month['compensation'],
            '15' => $month['minimum_wage'],
            '16' => 0.0,
            '17' => $month['benefits'],
            '18' => $month['de_minimis'],
            '19' => $month['contributions'],
            '20' => 0.0,
            '21' => round($month['compensation'] - $month['taxable'], 2),
            '22' => $month['taxable'],
            '23' => $month['not_withheld'],
            '24' => round($month['taxable'] - $month['not_withheld'], 2),
            '25' => $withheld,
            '26' => 0.0,
            '27' => $withheld,
            '31' => $withheld,
            '32' => $penalties !== null ? $surcharge : null,
            '33' => $penalties !== null ? $interest : null,
            '35' => $penalties !== null ? round($surcharge + $interest, 2) : null,
            '36' => round($withheld + $surcharge + $interest, 2),
        ];
    }
}
