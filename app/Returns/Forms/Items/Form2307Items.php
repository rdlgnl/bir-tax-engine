<?php

namespace App\Returns\Forms\Items;

use App\Profiles\Models\TaxProfile;
use App\Withholding\Services\WithholdingAgent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * BIR Form 2307 that the taxpayer, as payor, gives a payee they withheld
 * from: the quarter's payments to them by month, and the tax withheld.
 *
 * @phpstan-import-type Values from FormItems
 * @phpstan-import-type Payee from WithholdingAgent
 */
class Form2307Items extends ProfileFormItems
{
    /**
     * @param  Payee  $payee
     * @return Values
     */
    public function values(TaxProfile $profile, int $year, int $quarter, array $payee): array
    {
        $start = CarbonImmutable::create($year, $quarter * 3 - 2);
        $payeeTin = substr((string) preg_replace('/\D/', '', (string) $payee['tin']), 0, 9);
        [$tin1, $tin2, $tin3] = $this->tin($profile);
        $label = (string) config("tax.withholding.atcs.{$payee['atc']}.label", '');

        return [
            '1.from' => $start->format('mdY'),
            '1.to' => $start->addMonths(2)->endOfMonth()->format('mdY'),
            'payee.tin.1' => substr($payeeTin, 0, 3) ?: null,
            'payee.tin.2' => substr($payeeTin, 3, 3) ?: null,
            'payee.tin.3' => substr($payeeTin, 6, 3) ?: null,
            'payee.name' => Str::upper($payee['name']),
            'payor.tin.1' => $tin1,
            'payor.tin.2' => $tin2,
            'payor.tin.3' => $tin3,
            'payor.name' => $profile->trade_name ? Str::upper($profile->trade_name) : $this->name($profile),
            'payor.address' => $this->upper($profile->registered_address),
            'payor.zip' => $profile->zip_code,
            'row1.description' => $label,
            'row1.atc' => $payee['atc'],
            'row1.month1' => $payee['months'][0],
            'row1.month2' => $payee['months'][1],
            'row1.month3' => $payee['months'][2],
            'row1.total' => $payee['income'],
            'row1.withheld' => $payee['withheld'],
            'total.month1' => $payee['months'][0],
            'total.month2' => $payee['months'][1],
            'total.month3' => $payee['months'][2],
            'total.total' => $payee['income'],
            'total.withheld' => $payee['withheld'],
        ];
    }
}
