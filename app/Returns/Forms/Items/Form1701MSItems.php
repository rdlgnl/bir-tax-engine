<?php

namespace App\Returns\Forms\Items;

/**
 * BIR Form 1701-MS: the annual return for micro and small taxpayers (sales
 * below ₱20,000,000) on one tax regime. It takes the place of the 1701A
 * and, for those on itemized deductions, the 1701. The fourth quarter's
 * figures are the year's.
 *
 * A salary goes in Part IV A, its tax joins the business's (item 18B on
 * graduated rates, 25 on 8%), and what the employer withheld is a credit
 * (Part V item 5).
 */
class Form1701MSItems extends FormItems
{
    public function values(array $snapshot): array
    {
        $taxpayer = $snapshot['taxpayer'];
        $f = $snapshot['figures'];
        $eightPercent = $this->eightPercent($snapshot);
        $itemized = $this->itemized($snapshot);

        $values = [
            // MM/YYYY: the calendar year ends in December.
            '1.month' => '12',
            '1.year' => (string) $snapshot['year'],
            '2.no' => true,
            '3.no' => true,
            '6' => $taxpayer['tin'],
            '7' => $taxpayer['rdo'],
            '8' => $this->fullName($taxpayer),
            '9' => $taxpayer['email'] ?? null,
            '10' => isset($taxpayer['contact']) ? preg_replace('/[^\d+]/', '', $taxpayer['contact']) : null,
            match ($taxpayer['income']) {
                'mixed' => '11.mixed',
                'profession' => '11.profession',
                default => '11.business',
            } => true,
            '12.eight_percent' => $eightPercent,
            '12.graduated' => ! $eightPercent,
            '17.itemized' => ! $eightPercent && $itemized,
            '17.osd' => ! $eightPercent && ! $itemized,
            '18' => $taxpayer['atc'],
            'p2.tin' => $this->tinDigits($taxpayer),
            'p2.last_name' => $this->lastName($taxpayer),
        ];

        $sales = $this->peso($f['gross']);
        $taxDue = $this->peso($f['income_tax']);
        $salary = $f['compensation'];
        $salaryTaxable = $salary !== null ? $this->peso($salary['taxable']) : 0;
        $salaryTax = $salary !== null ? $this->peso($salary['tax']) : 0;
        $salaryWithheld = $salary !== null ? $this->peso($salary['withheld']) : 0;
        $totalTax = $taxDue + $salaryTax;

        if ($salary !== null) {
            $values += [
                'iv.a1' => $this->peso($salary['gross']),
                'iv.a2' => $this->peso($salary['non_taxable']),
                'iv.a3' => $salaryTaxable,
                // On graduated rates the salary is taxed together with the business (item 18B).
                ...($eightPercent ? ['iv.a4' => $salaryTax] : []),
            ];
        }

        if ($eightPercent) {
            $reduction = $this->peso($f['exempt_amount']);

            $values += [
                'iv.19' => $sales,
                'iv.21' => $sales,
                'iv.22' => $reduction,
                'iv.23' => $sales - $reduction,
                'iv.24' => $taxDue,
                'iv.25' => $totalTax,
            ];
        } else {
            $deduction = $this->peso($f['deduction']);
            $taxable = $sales - $deduction;

            $values += [
                'iv.5' => $sales,
                'iv.7' => $sales,
            ] + ($itemized ? [
                // No cost of sales is recorded for services, so gross income is the net sales.
                'iv.9' => $sales,
                'iv.10A' => $deduction,
                'iv.10D' => $deduction,
            ] : [
                'iv.11' => $deduction,
            ]) + [
                'iv.12' => $taxable,
                'iv.14' => $taxable,
                'iv.15' => $salaryTaxable + $taxable,
                'iv.18B' => $totalTax,
            ];
        }

        $paid = $this->peso($f['prior_payments']);
        $withheldBefore = $this->peso($f['withheld'] - $f['quarter_withheld']);
        $withheldNow = $this->peso($f['quarter_withheld']);
        $credits = $paid + $withheldBefore + $withheldNow + $salaryWithheld;
        $payable = $totalTax - $credits;
        [$surcharge, $interest, $penalties] = $this->penalties($snapshot);

        return $values + [
            'v.2' => $paid,
            'v.3' => $withheldBefore,
            'v.4' => $withheldNow,
            ...($salary !== null ? ['v.5' => $salaryWithheld] : []),
            'v.10' => $credits,
            '22A' => $totalTax,
            '23A' => $totalTax,
            '24A' => $credits,
            '25A' => $payable,
            '27A' => $payable,
            '28A' => $surcharge,
            '28B' => $interest,
            '28D' => $penalties,
            '29A' => $payable + $penalties,
            '30' => $payable + $penalties,
        ];
    }
}
