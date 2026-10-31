<?php

namespace App\Returns\Forms\Items;

/**
 * BIR Form 1701-MS: the annual return for micro and small taxpayers (sales
 * below ₱20,000,000) on one tax regime. It takes the place of the 1701A
 * and, for those on itemized deductions, the 1701. The fourth quarter's
 * figures are the year's.
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
            $taxpayer['income'] === 'profession' ? '11.profession' : '11.business' => true,
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

        if ($eightPercent) {
            $reduction = $this->peso($f['exempt_amount']);

            $values += [
                'iv.19' => $sales,
                'iv.21' => $sales,
                'iv.22' => $reduction,
                'iv.23' => $sales - $reduction,
                'iv.24' => $taxDue,
                'iv.25' => $taxDue,
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
                'iv.15' => $taxable,
                'iv.18B' => $taxDue,
            ];
        }

        $paid = $this->peso($f['prior_payments']);
        $withheldBefore = $this->peso($f['withheld'] - $f['quarter_withheld']);
        $withheldNow = $this->peso($f['quarter_withheld']);
        $credits = $paid + $withheldBefore + $withheldNow;
        $payable = $taxDue - $credits;

        return $values + [
            'v.2' => $paid,
            'v.3' => $withheldBefore,
            'v.4' => $withheldNow,
            'v.10' => $credits,
            '22A' => $taxDue,
            '23A' => $taxDue,
            '24A' => $credits,
            '25A' => $payable,
            '27A' => $payable,
            '29A' => $payable,
            '30' => $payable,
        ];
    }
}
