<?php

namespace App\Returns\Forms\Items;

use App\Payroll\Services\Payroll;
use App\Profiles\Models\TaxProfile;
use App\Tax\Services\Penalties;
use Carbon\CarbonImmutable;

/**
 * BIR Form 1601-C: a month's tax withheld on salaries. All pay is taxable
 * here (the app records taxable pay), so the non-taxable lines stay at
 * zero and what was withheld is what's remitted.
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
            '21' => 0.0,
            '22' => $month['compensation'],
            '24' => $month['compensation'],
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
