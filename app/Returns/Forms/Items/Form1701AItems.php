<?php

namespace App\Returns\Forms\Items;

/**
 * BIR Form 1701A: the annual return for income purely from business or
 * profession, on 8% or on graduated rates with the optional standard
 * deduction. The fourth quarter's figures are the year's.
 */
class Form1701AItems extends FormItems
{
    public function values(array $snapshot): array
    {
        $taxpayer = $snapshot['taxpayer'];
        $f = $snapshot['figures'];
        $eightPercent = $this->eightPercent($snapshot);

        $values = $this->identity($taxpayer, [
            'tin' => ['tin.1', 'tin.2', 'tin.3'],
            'rdo' => '5',
            'name' => '8',
            'address' => ['9.1', 40, '9.2'],
            'zip' => '9A',
            'birth_date' => '10',
            'email' => '11',
            'citizenship' => '12',
            'contact' => '15',
        ]) + [
            // MM/YYYY: the calendar year ends in December.
            '1' => '12'.$snapshot['year'],
            '2.no' => true,
            '3.no' => true,
            $taxpayer['income'] === 'profession' ? '6.professional' : '6.single_proprietor' => true,
            "atc.{$taxpayer['atc']}" => true,
            '13.no' => true,
            '19.graduated_osd' => ! $eightPercent,
            '19.eight_percent' => $eightPercent,
            'p2.tin' => $this->tinDigits($taxpayer),
            'p2.last_name' => $this->lastName($taxpayer),
        ];

        $sales = $this->peso($f['gross']);
        $taxDue = $this->peso($f['income_tax']);

        if ($eightPercent) {
            $reduction = $this->peso($f['exempt_amount']);

            $values += [
                '47A' => $sales,
                '49A' => $sales,
                '53A' => $sales,
                '54A' => $reduction,
                '55A' => $sales - $reduction,
                '56A' => $taxDue,
            ];
        } else {
            $deduction = $this->peso($f['deduction']);

            $values += [
                '36A' => $sales,
                '38A' => $sales,
                '39A' => $deduction,
                '40A' => $sales - $deduction,
                '45A' => $sales - $deduction,
                '46A' => $taxDue,
            ];
        }

        $paid = $this->peso($f['prior_payments']);
        $withheldBefore = $this->peso($f['withheld'] - $f['quarter_withheld']);
        $withheldNow = $this->peso($f['quarter_withheld']);
        $credits = $paid + $withheldBefore + $withheldNow;
        $payable = $taxDue - $credits;

        return $values + [
            '58A' => $paid,
            '59A' => $withheldBefore,
            '60A' => $withheldNow,
            '64A' => $credits,
            '65A' => $payable,
            '20A' => $taxDue,
            '21A' => $credits,
            '22A' => $payable,
            '24A' => $payable,
            '28A' => 0,
            '29A' => $payable,
            '30' => $payable,
        ];
    }
}
