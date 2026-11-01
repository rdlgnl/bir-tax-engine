<?php

namespace App\Returns\Forms\Items;

use Illuminate\Support\Str;

/**
 * BIR Form 1701: the annual return for mixed-income earners and anyone on
 * itemized deductions. The fourth quarter's figures are the year's.
 *
 * A salary goes in Part V Schedules 1 and 2, its tax joins the business's
 * (items 24-25 on graduated rates, 32 on 8%), and what the employer
 * withheld is a credit (Part VII item 5).
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
        $salary = $f['compensation'];
        $salaryTaxable = $salary !== null ? $this->peso($salary['taxable']) : 0;
        $salaryTax = $salary !== null ? $this->peso($salary['tax']) : 0;
        $salaryWithheld = $salary !== null ? $this->peso($salary['withheld']) : 0;
        $totalTax = $taxDue + $salaryTax;

        if ($salary !== null) {
            $compensation = $this->peso($salary['gross']);

            $values += [
                'S1.1.taxpayer' => true,
                'S1.1.employer' => $salary['employer'] !== null ? Str::upper($salary['employer']) : null,
                // Nine digits and a five-digit branch code.
                'S1.1.tin' => ($salary['employer_tin'] ?? null) !== null ? $this->tin14($salary['employer_tin']) : null,
                'S1.1.compensation' => $compensation,
                'S1.1.withheld' => $salaryWithheld,
                'S1.3A.compensation' => $compensation,
                'S1.3A.withheld' => $salaryWithheld,
                'S2.4' => $compensation,
                'S2.5' => $this->peso($salary['non_taxable']),
                'S2.6' => $salaryTaxable,
                // On graduated rates the salary is taxed together with the business (item 25).
                ...($eightPercent ? ['S2.7' => $salaryTax] : []),
            ];
        }

        if ($eightPercent) {
            $reduction = $this->peso($f['exempt_amount']);

            $values += [
                'S3B.26' => $sales,
                'S3B.28' => $sales,
                'S3B.29' => $reduction,
                'S3B.30' => $sales - $reduction,
                'S3B.31' => $taxDue,
                'S3B.32' => $totalTax,
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
                'S3A.24' => $salaryTaxable + $sales - $deduction,
                'S3A.25' => $totalTax,
            ];
        }

        $paid = $this->peso($f['prior_payments']);
        $withheldBefore = $this->peso($f['withheld'] - $f['quarter_withheld']);
        $withheldNow = $this->peso($f['quarter_withheld']);
        $credits = $paid + $withheldBefore + $withheldNow + $salaryWithheld;
        $payable = $totalTax - $credits;

        return $values + [
            'VI.1' => $totalTax,
            'VI.5' => $totalTax,
            'VII.2' => $paid,
            'VII.3' => $withheldBefore,
            'VII.4' => $withheldNow,
            ...($salary !== null ? ['VII.5' => $salaryWithheld] : []),
            'VII.10' => $credits,
            '22A' => $totalTax,
            '23A' => $credits,
            '24A' => $payable,
            '26A' => $payable,
            '30A' => 0,
            '31A' => $payable,
            '32' => $payable,
        ];
    }

    private function tin14(string $tin): string
    {
        $digits = (string) preg_replace('/\D/', '', $tin);

        return substr($digits, 0, 9).str_pad(substr($digits, 9, 5), 5, '0', STR_PAD_LEFT);
    }
}
