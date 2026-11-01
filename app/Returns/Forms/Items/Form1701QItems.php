<?php

namespace App\Returns\Forms\Items;

/**
 * BIR Form 1701Q: the quarterly income tax return.
 *
 * The engine's figures are year-to-date; the form wants this quarter's
 * declaration plus what the previous quarter's return carried, so the
 * previous quarter's figures fill items 42 and 50.
 */
class Form1701QItems extends FormItems
{
    public function values(array $snapshot): array
    {
        $taxpayer = $snapshot['taxpayer'];
        $f = $snapshot['figures'];
        $previous = $snapshot['previous_figures'];
        $eightPercent = $this->eightPercent($snapshot);
        $itemized = $this->itemized($snapshot);

        $values = $this->identity($taxpayer, [
            'tin' => ['tin.1', 'tin.2', 'tin.3'],
            'rdo' => '6',
            'name' => '9',
            'address' => ['10.1', 40, '10.2'],
            'zip' => '10A',
            'birth_date' => '11',
            'email' => '12',
            'citizenship' => '13',
        ]) + [
            '1' => (string) $snapshot['year'],
            "2.{$snapshot['quarter']}" => true,
            '3.yes' => $snapshot['amendment'] !== null,
            '3.no' => $snapshot['amendment'] === null,
            $taxpayer['income'] === 'profession' ? '7.professional' : '7.single_proprietor' => true,
            "atc.{$taxpayer['atc']}" => true,
            '15.no' => true,
            '16.graduated' => ! $eightPercent,
            '16.eight_percent' => $eightPercent,
            '16A.itemized' => ! $eightPercent && $itemized,
            '16A.osd' => ! $eightPercent && ! $itemized,
            'p2.tin' => $this->tinDigits($taxpayer),
            'p2.last_name' => $this->lastName($taxpayer),
        ];

        $sales = $this->peso($f['quarter_gross']);
        $taxDue = $this->peso($f['income_tax']);

        if ($eightPercent) {
            $toDate = $sales + $this->peso($previous['gross'] ?? 0);
            $reduction = $this->peso($f['exempt_amount']);

            $values += [
                '47A' => $sales,
                '49A' => $sales,
                '50A' => $toDate - $sales,
                '51A' => $toDate,
                '52A' => $reduction,
                '53A' => $toDate - $reduction,
                '54A' => $taxDue,
            ];
        } else {
            $deduction = $this->peso($f['deduction'] - ($previous['deduction'] ?? 0));
            $net = $sales - $deduction;
            $previousTaxable = $this->peso($previous['taxable'] ?? 0);

            $values += [
                '36A' => $sales,
                '38A' => $sales,
                $itemized ? '39A' : '40A' => $deduction,
                '41A' => $net,
                '42A' => $previousTaxable,
                '45A' => $net + $previousTaxable,
                '46A' => $taxDue,
            ];
        }

        $paid = $this->peso($f['prior_payments']);
        $withheldBefore = $this->peso($f['withheld'] - $f['quarter_withheld']);
        $withheldNow = $this->peso($f['quarter_withheld']);
        // An amended return credits what the returns filed before it paid.
        $previouslyFiled = $this->peso($snapshot['amendment']['previously_paid'] ?? 0.0);
        $credits = $paid + $withheldBefore + $withheldNow + $previouslyFiled;
        $payable = $taxDue - $credits;
        [$surcharge, $interest, $penalties] = $this->penalties($snapshot);

        return $values + [
            '64A' => $surcharge,
            '65A' => $interest,
            '56A' => $paid,
            '57A' => $withheldBefore,
            '58A' => $withheldNow,
            '59A' => $previouslyFiled,
            '62A' => $credits,
            '63A' => $payable,
            '67A' => $penalties,
            '68A' => $payable + $penalties,
            '26A' => $taxDue,
            '27A' => $credits,
            '28A' => $payable,
            '29A' => $penalties,
            '30A' => $payable + $penalties,
            '31' => $payable + $penalties,
        ];
    }
}
