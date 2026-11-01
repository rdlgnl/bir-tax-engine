<?php

namespace App\Returns\Forms\Items;

use App\Tax\Services\TaxRules;

/**
 * BIR Form 2551Q: the quarterly percentage tax return, in pesos and
 * centavos. Only taxpayers on graduated rates who aren't VAT-registered
 * file it, so item 13 (asked once a year, in the first quarter) is always
 * "graduated income tax rate".
 */
class Form2551QItems extends FormItems
{
    public function values(array $snapshot): array
    {
        $taxpayer = $snapshot['taxpayer'];
        $f = $snapshot['figures'];
        $sales = round($f['quarter_gross'], 2);
        $tax = round((float) $f['percentage_tax'], 2);
        // The quarter's own rate: 1% from July 2020 to June 2023, 3% otherwise.
        $rate = (string) round(app(TaxRules::class)->forQuarter($snapshot['year'], $snapshot['quarter'])->rate('percentage_tax_rate') * 100);

        $values = $this->identity($taxpayer, [
            'tin' => ['tin.1', 'tin.2', 'tin.3'],
            'rdo' => '7',
            'name' => '8',
            'address' => ['9.1', 40, '9.2'],
            'zip' => '9A',
            'contact' => '10',
            'email' => '11',
        ]);

        return $values + [
            '1.calendar' => true,
            // Year ended, MM/YYYY.
            '2' => '12'.$snapshot['year'],
            "3.{$snapshot['quarter']}" => true,
            '4.no' => true,
            '12.no' => true,
            '13.graduated' => $snapshot['quarter'] === 1,
            'p2.tin' => $this->tinDigits($taxpayer),
            'p2.last_name' => $this->lastName($taxpayer),

            'S1.1.atc' => $taxpayer['atc'],
            'S1.1.taxable' => $sales,
            'S1.1.rate' => str_pad($rate, 2, ' ', STR_PAD_LEFT),
            'S1.1.due' => $tax,
            'S1.7' => $tax,
            '14' => $tax,
            '18' => 0,
            '19' => $tax,
            '23' => 0,
            '24' => $tax,
        ];
    }
}
