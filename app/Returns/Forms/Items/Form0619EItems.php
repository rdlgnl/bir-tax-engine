<?php

namespace App\Returns\Forms\Items;

use App\Profiles\Models\TaxProfile;
use Carbon\CarbonImmutable;

/**
 * BIR Form 0619-E: one month's expanded withholding, remitted by the 10th
 * of the next month. The ATC and tax type are printed on the form.
 *
 * @phpstan-import-type Values from FormItems
 */
class Form0619EItems extends ProfileFormItems
{
    /**
     * @return Values
     */
    public function values(TaxProfile $profile, int $year, int $month, float $withheld, CarbonImmutable $due): array
    {
        return $this->identity($profile, [
            'tin' => ['tin.1', 'tin.2', 'tin.3'],
            'rdo' => '8',
            'name' => '9',
            'address' => ['10.1', 40, '10.2'],
            'zip' => '10A',
            'contact' => '11',
            'email' => '13',
        ]) + [
            '1' => CarbonImmutable::create($year, $month)->format('mY'),
            '2' => $due->format('mdY'),
            '3.no' => true,
            '4.yes' => $withheld > 0,
            '4.no' => $withheld <= 0,
            '12.private' => true,
            '14' => $withheld,
            '16' => $withheld,
            '18' => $withheld,
        ];
    }
}
