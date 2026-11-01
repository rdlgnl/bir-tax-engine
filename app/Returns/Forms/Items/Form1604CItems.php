<?php

namespace App\Returns\Forms\Items;

use App\Payroll\Services\Payroll;
use App\Profiles\Models\TaxProfile;
use App\Returns\Models\Filing;

/**
 * BIR Form 1604-C: the year's tax withheld on salaries, a line per month's
 * 1601-C. What the year-end adjustment refunded to employees was remitted
 * before it was refunded, so it's an over-remittance, credited from the
 * next January.
 *
 * @phpstan-import-type Values from FormItems
 * @phpstan-import-type Annual from Payroll
 */
class Form1604CItems extends ProfileFormItems
{
    /**
     * @param  Annual  $annual
     * @return Values
     */
    public function values(TaxProfile $profile, array $annual): array
    {
        $values = $this->identity($profile, [
            'tin' => ['tin.1', 'tin.2', 'tin.3'],
            'rdo' => '5',
            'name' => '6',
            'address' => ['7.1', 40, '7.2'],
            'zip' => '7A',
            'contact' => '9',
            'email' => '10',
        ]) + [
            '1' => substr((string) $annual['year'], -2),
            '2.no' => true,
            'tin.branch' => '00000',
            '8.private' => true,
            '8A.no' => true,
            '11.yes' => $annual['refund'] > 0,
            '11.no' => $annual['refund'] <= 0,
            '12' => $annual['refund'] > 0 ? $annual['refund'] : null,
            '13' => $annual['refund'] > 0 ? '01' : null,
        ];

        $filings = Filing::query()->where('year', $annual['year'])->where('form', '1601-C')->get()->keyBy('month');
        $remitted = 0.0;

        foreach ($annual['months'] as $month) {
            $filing = $filings->get($month['month']);
            $paid = $filing?->amount_paid !== null ? (float) $filing->amount_paid : $month['withheld'];
            $remitted += $paid;
            $values += [
                "m.{$month['month']}.date" => $filing?->filed_on->format('m/d/Y'),
                "m.{$month['month']}.withheld" => $month['withheld'],
                "m.{$month['month']}.remitted" => $paid,
            ];
        }

        return $values + [
            'm.total.withheld' => $annual['withheld'],
            'm.total.remitted' => round($remitted, 2),
        ];
    }
}
