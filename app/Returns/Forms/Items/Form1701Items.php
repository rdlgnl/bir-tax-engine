<?php

namespace App\Returns\Forms\Items;

/**
 * BIR Form 1701: the annual return for mixed-income earners and anyone on
 * itemized deductions. The fourth quarter's figures are the year's.
 *
 * The app keeps no record of salaries, so a mixed-income earner still copies
 * their compensation (Part V Schedules 1 and 2) from their 2316.
 */
class Form1701Items extends FormItems
{
    public function values(array $snapshot): array
    {
        $taxpayer = $snapshot['taxpayer'];
        $f = $snapshot['figures'];
        $eightPercent = $this->eightPercent($snapshot);
        $itemized = $this->itemized($snapshot);
        $tin = $this->tinDigits($taxpayer);
        $lastName = $this->lastName($taxpayer);

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
            '1' => (string) $snapshot['year'],
            '2.no' => true,
            '3.no' => true,
            $taxpayer['income'] === 'profession' ? '6.professional' : '6.single_proprietor' => true,
            '6.compensation_earner' => $taxpayer['income'] === 'mixed',
            "atc.{$taxpayer['atc']}" => true,
            '13.no' => true,
            '19.no' => true,
            '20.no' => true,
            '21.graduated' => ! $eightPercent,
            '21.eight_percent' => $eightPercent,
            '21A.itemized' => ! $eightPercent && $itemized,
            '21A.osd' => ! $eightPercent && ! $itemized,
            'p2.tin' => $tin,
            'p2.last_name' => $lastName,
            'p3.tin' => $tin,
            'p3.last_name' => $lastName,
            'p4.tin' => $tin,
            'p4.last_name' => $lastName,
        ];

        $sales = $this->peso($f['gross']);
        $taxDue = $this->peso($f['income_tax']);

        if ($eightPercent) {
            $reduction = $this->peso($f['exempt_amount']);

            $values += [
                'S3B.26' => $sales,
                'S3B.28' => $sales,
                'S3B.29' => $reduction,
                'S3B.30' => $sales - $reduction,
                'S3B.31' => $taxDue,
                'S3B.32' => $taxDue,
            ];
        } else {
            $deduction = $this->peso($f['deduction']);

            $values += [
                'S3A.8' => $sales,
                'S3A.10' => $sales,
                'S3A.12' => $sales,
                ...($itemized
                    ? ['S3A.13' => $deduction, 'S3A.16' => $deduction, 'S4.18' => $deduction]
                    : ['S3A.17' => $deduction]),
                'S3A.18' => $sales - $deduction,
                'S3A.23' => $sales - $deduction,
                'S3A.24' => $sales - $deduction,
                'S3A.25' => $taxDue,
            ];
        }

        $paid = $this->peso($f['prior_payments']);
        $withheldBefore = $this->peso($f['withheld'] - $f['quarter_withheld']);
        $withheldNow = $this->peso($f['quarter_withheld']);
        $credits = $paid + $withheldBefore + $withheldNow;
        $payable = $taxDue - $credits;

        return $values + [
            'VI.1' => $taxDue,
            'VI.5' => $taxDue,
            'VII.2' => $paid,
            'VII.3' => $withheldBefore,
            'VII.4' => $withheldNow,
            'VII.10' => $credits,
            '22A' => $taxDue,
            '23A' => $credits,
            '24A' => $payable,
            '26A' => $payable,
            '30A' => 0,
            '31A' => $payable,
            '32' => $payable,
        ];
    }
}
