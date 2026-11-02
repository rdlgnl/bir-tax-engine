<?php

namespace App\Returns\Forms\Items;

use App\Corporations\Models\Corporation;
use App\Corporations\Services\CorporateTax;

/**
 * BIR Form 1702-RT for a corporation on the regular rate: Part IV's
 * computation for the year, with the quarters' payments and the 2307s as
 * credits, and the totals on page 1.
 *
 * The itemized deductions go on Schedule I as one total, and the earlier
 * years' losses (NOLCO, item 36) and excess MCIT (item 47) as the app
 * carried them; breaking the deductions down, the NOLCO and MCIT schedules
 * and the reconciliation with the books (pages 3 and 4) are for the
 * corporation's accountant.
 *
 * @phpstan-import-type Values from FormItems
 * @phpstan-import-type AnnualEstimate from CorporateTax
 */
class Form1702RTItems extends CorporationFormItems
{
    /**
     * @param  AnnualEstimate  $annual
     * @return Values
     */
    public function values(Corporation $corporation, array $annual): array
    {
        $f = $annual['figures'];
        $osd = $corporation->deduction_method === 'osd';
        $sales = $this->peso($f['sales']);
        $cost = $this->peso($f['cost_of_sales']);
        $other = $this->peso($f['other_income']);
        $grossIncome = $sales - $cost + $other;
        $deductions = $osd ? $this->peso($grossIncome * (float) config('tax.corporation.osd_rate')) : $this->peso($f['deductions']);
        $nolco = $this->peso($annual['nolco']);
        $taxable = $grossIncome - $deductions - $nolco;
        $regular = $this->peso(max(0, $taxable) * $annual['rate']);
        // Each quarter at its own rate (1% until June 2023, then 2%).
        $mcit = $annual['mcit_applies'] ? $this->peso($annual['mcit']) : null;
        $due = max($regular, $mcit ?? 0);
        $credits = [
            '45' => $this->peso($annual['paid_mcit']),
            '46' => $this->peso($annual['paid_regular']),
            '47' => $this->peso($annual['excess_mcit_applied']),
            '48' => $this->peso($annual['withheld_previous']),
            '49' => $this->peso($annual['withheld_fourth_quarter']),
        ];
        $totalCredits = array_sum($credits);
        $usesMcit = $mcit !== null && $mcit > $regular;
        [$tin1, $tin2, $tin3] = $this->tin($corporation);
        $tin = $tin1.$tin2.$tin3 ?: null;
        $name = $this->upper($corporation->name);
        [$name1, $rest] = $this->wrap($name, 38);
        [$name2, $name3] = $this->wrap($rest, 38);
        [$address1, $rest] = $this->wrap($this->upper($corporation->registered_address), 38);
        [$address2, $address3] = $this->wrap($rest, 38);

        return [
            '1.calendar' => true,
            '2.month' => '12',
            '2.year' => substr((string) $annual['year'], 2),
            '3.no' => true,
            '4.no' => true,
            'atc.mcit' => $usesMcit,
            'atc.code' => $usesMcit ? null : (string) config('tax.corporation.atc.regular'),
            'atc.description' => $usesMcit ? null : 'DOMESTIC CORPORATION IN GENERAL',
            'atc.other' => ! $usesMcit,
            'tin.1' => $tin1,
            'tin.2' => $tin2,
            'tin.3' => $tin3,
            '7' => $corporation->rdo_code,
            '8.1' => $name1,
            '8.2' => $name2,
            '8.3' => $name3,
            '9.1' => $address1,
            '9.2' => $address2,
            '9.3' => $address3,
            '9A' => $corporation->zip_code,
            '11' => preg_replace('/[^\d+]/', '', (string) $corporation->contact_number) ?: null,
            '12' => $this->upper($corporation->email),
            '13.itemized' => ! $osd,
            '13.osd' => $osd,
            '14' => $due,
            '15' => $totalCredits,
            '16' => $due - $totalCredits,
            '21' => $due - $totalCredits,
            'p2.tin' => $tin,
            'p2.name' => $name,
            'p3.tin' => $tin,
            'p3.name' => $name,
            'p4.tin' => $tin,
            'p4.name' => $name,
            '27' => $sales,
            '29' => $sales,
            '30' => $cost,
            '31' => $sales - $cost,
            '32' => $other,
            '33' => $grossIncome,
            '34' => $osd ? null : $deductions,
            '36' => $osd ? null : $nolco,
            '37' => $osd ? null : $deductions + $nolco,
            '38' => $osd ? $deductions : null,
            '39' => $taxable,
            '40' => (string) round($annual['rate'] * 100),
            '41' => $regular,
            '42' => $mcit,
            '43' => $due,
            '55' => $totalCredits,
            '56' => $due - $totalCredits,
            's1.18' => $osd ? null : $deductions,
        ] + $credits;
    }
}
