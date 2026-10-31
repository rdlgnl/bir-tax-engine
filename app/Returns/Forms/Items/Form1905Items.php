<?php

namespace App\Returns\Forms\Items;

use App\Profiles\Models\TaxProfile;
use Carbon\CarbonImmutable;

/**
 * BIR Form 1905 for taking percentage tax off the COR after choosing the
 * 8% option: item 6 says why, 7G cancels the tax type from the start of the
 * year the option was chosen for.
 *
 * @phpstan-import-type Values from FormItems
 */
class Form1905Items extends ProfileFormItems
{
    /**
     * @return Values
     */
    public function removePercentageTax(TaxProfile $profile, int $year): array
    {
        [$tin1, $tin2, $tin3] = $this->tin($profile);
        $effective = CarbonImmutable::create($year)->format('mdY');

        return [
            'tin.1' => $tin1,
            'tin.2' => $tin2,
            'tin.3' => $tin3,
            '2' => $profile->rdo_code,
            '3' => preg_replace('/[^\d+]/', '', (string) $profile->contact_number),
            '4' => $this->name($profile),
            '6.eight_percent' => true,
            '7G' => true,
            '7G.cancelled' => 'PERCENTAGE TAX',
            '7G.cancelled_form' => '2551Q',
            '7G.cancelled_date' => $effective,
            '11.1' => "AVAILED OF THE 8% INCOME TAX RATE OPTION FOR TAXABLE YEAR {$year}",
            '11.2' => 'IN LIEU OF GRADUATED RATES AND PERCENTAGE TAX; CANCEL PERCENTAGE TAX (2551Q)',
            '11.date' => $effective,
            '12' => $this->printedName($profile),
        ];
    }
}
