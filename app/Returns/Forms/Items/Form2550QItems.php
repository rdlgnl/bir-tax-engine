<?php

namespace App\Returns\Forms\Items;

use App\Returns\Services\ReturnForm;
use Carbon\CarbonImmutable;
use LogicException;

/**
 * BIR Form 2550Q: the quarterly VAT return. Sales net of VAT and the VAT on
 * them, input VAT from purchase invoices in the taxpayer's name, and any
 * excess input VAT carried over from the quarter before. Amounts keep
 * centavos, as the form shows them.
 *
 * @phpstan-import-type Snapshot from ReturnForm
 */
class Form2550QItems extends FormItems
{
    public function values(array $snapshot): array
    {
        $vat = $snapshot['vat'] ?? throw new LogicException('A 2550Q needs the quarter’s VAT figures.');
        $taxpayer = $snapshot['taxpayer'];
        $year = $snapshot['year'];
        $quarter = $snapshot['quarter'];
        $start = CarbonImmutable::create($year, $quarter * 3 - 2);

        $values = $this->identity($taxpayer, [
            'tin' => ['tin.1', 'tin.2', 'tin.3'],
            'rdo' => '8',
            'name' => '9',
            'address' => ['10.1', 40, '10.2'],
            'zip' => '10A',
            'contact' => '11',
            'email' => '12',
        ]) + [
            '1.calendar' => true,
            // MM/YYYY: the calendar year ends in December.
            '2' => '12'.$year,
            "3.{$quarter}" => true,
            '4.from' => $start->format('mdY'),
            '4.to' => $start->addMonths(2)->endOfMonth()->format('mdY'),
            '5.no' => true,
            '6.no' => true,
            $this->classification((float) $snapshot['figures']['gross']) => true,
            '14.no' => true,
            'p2.tin' => $this->tinDigits($taxpayer),
            'p2.name' => $this->lastName($taxpayer),
        ];

        $sales = round($vat['vatable_sales'] + $vat['zero_rated_sales'] + $vat['exempt_sales'], 2);
        $available = round($vat['carried_over'] + $vat['input_vat'], 2);
        $net = round($vat['output_vat'] - $available, 2);

        return $values + [
            '31A' => $vat['vatable_sales'],
            '31B' => $vat['output_vat'],
            '32A' => $vat['zero_rated_sales'],
            '33A' => $vat['exempt_sales'],
            '34A' => $sales,
            '34B' => $vat['output_vat'],
            '37B' => $vat['output_vat'],
            '38B' => $vat['carried_over'],
            '43B' => $vat['carried_over'],
            '44A' => $vat['purchases'],
            '44B' => $vat['input_vat'],
            '50A' => $vat['purchases'],
            '50B' => $vat['input_vat'],
            '51B' => $available,
            '57B' => 0,
            '59B' => 0,
            '60B' => $available,
            // Negative means excess input VAT, printed in brackets.
            '61B' => $net,
            '15' => $net,
            '20' => 0,
            '21' => $net,
            '26' => $net,
        ];
    }

    /**
     * Micro below ₱3,000,000 in sales, small below ₱20,000,000, medium
     * below ₱1,000,000,000, large above (EOPT).
     */
    private function classification(float $gross): string
    {
        return match (true) {
            $gross < 3_000_000 => '13.micro',
            $gross < 20_000_000 => '13.small',
            $gross < 1_000_000_000 => '13.medium',
            default => '13.large',
        };
    }
}
