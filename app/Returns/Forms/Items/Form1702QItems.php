<?php

namespace App\Returns\Forms\Items;

use App\Corporations\Models\Corporation;
use App\Corporations\Services\CorporateTax;
use Illuminate\Support\Str;

/**
 * BIR Form 1702Q for a corporation on the regular rate: Schedule 2 with
 * this quarter's figures and the taxable income of the quarters before,
 * Schedule 3's MCIT once it applies, and Schedule 4's credits.
 *
 * Amounts are whole pesos, and each total is added up from the amounts as
 * printed. The prior year's excess credits and MCIT carry-over stay blank
 * for the corporation's accountant to fill in.
 *
 * @phpstan-import-type Values from FormItems
 * @phpstan-import-type QuarterTax from CorporateTax
 */
class Form1702QItems
{
    /**
     * @param  QuarterTax  $tax
     * @return Values
     */
    public function values(Corporation $corporation, array $tax): array
    {
        $q = $tax['this_quarter'];
        $sales = $this->peso($q['sales']);
        $cost = $this->peso($q['cost_of_sales']);
        $other = $this->peso($q['other_income']);
        $deductions = $this->peso($q['deductions']);
        $previous = $this->peso($tax['previous_taxable_income']);
        $taxable = $sales - $cost + $other - $deductions + $previous;
        $regular = $this->peso(max(0, $taxable) * $tax['rate']);
        $gross = array_map(fn (float $amount) => $this->peso($amount), $tax['gross_by_quarter']);
        $mcit = $tax['mcit_applies'] ? $this->peso(array_sum($gross) * (float) config('tax.corporation.mcit_rate')) : null;
        $due = max($regular, $mcit ?? 0);
        $credits = [
            's4.2' => $this->peso($tax['paid_regular']),
            's4.3' => $this->peso($tax['paid_mcit']),
            's4.4' => $this->peso($tax['withheld_previous']),
            's4.5' => $this->peso($tax['withheld_this_quarter']),
        ];
        $totalCredits = array_sum($credits);
        $usesMcit = $mcit !== null && $mcit > $regular;
        [$tin1, $tin2, $tin3] = $this->tin($corporation);
        [$name1, $name2] = $this->wrap($this->upper($corporation->name), 38);
        [$address1, $address2] = $this->wrap($this->upper($corporation->registered_address), 38);

        $values = [
            '1.calendar' => true,
            '2.month' => '12',
            '2.year' => substr((string) $tax['year'], 2),
            "3.{$tax['quarter']}" => true,
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
            '9.1' => $address1,
            '9.2' => $address2,
            '9A' => $corporation->zip_code,
            '10' => preg_replace('/[^\d+]/', '', (string) $corporation->contact_number) ?: null,
            '11' => $this->upper($corporation->email),
            '12.itemized' => $corporation->deduction_method === 'itemized',
            '12.osd' => $corporation->deduction_method === 'osd',
            '13.no' => true,
            '14' => $due,
            '16' => $due,
            '18' => $due,
            '19' => $totalCredits,
            '20' => $due - $totalCredits,
            '25' => $due - $totalCredits,
            'p2.tin' => $tin1.$tin2.$tin3 ?: null,
            'p2.name' => $this->upper($corporation->name),
            's2.1' => $sales,
            's2.2' => $cost,
            's2.3' => $sales - $cost,
            's2.4' => $other,
            's2.5' => $sales - $cost + $other,
            's2.6' => $deductions,
            's2.7' => $sales - $cost + $other - $deductions,
            's2.8' => $previous,
            's2.9' => $taxable,
            's2.10' => (string) round($tax['rate'] * 100),
            's2.11' => $regular,
            's2.12' => $mcit,
            's2.13' => $due,
            ...$credits,
            's4.7' => $totalCredits,
        ];

        if ($mcit !== null) {
            foreach ($gross as $i => $amount) {
                $values['s3.'.($i + 1)] = $amount;
            }

            $values['s3.4'] = array_sum($gross);
            $values['s3.6'] = $mcit;
        }

        return $values;
    }

    /**
     * @return array{string, string, string}
     */
    private function tin(Corporation $corporation): array
    {
        $digits = substr(preg_replace('/\D/', '', (string) $corporation->tin), 0, 9);

        return [substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6, 3)];
    }

    /**
     * Text over two rows of boxes, broken between words.
     *
     * @return array{string, string}
     */
    private function wrap(?string $text, int $width): array
    {
        $text ??= '';

        if (mb_strlen($text) <= $width) {
            return [$text, ''];
        }

        $first = Str::of(mb_substr($text, 0, $width + 1))->beforeLast(' ')->trim()->value() ?: mb_substr($text, 0, $width);

        return [$first, trim(mb_substr($text, mb_strlen($first)))];
    }

    private function upper(?string $value): ?string
    {
        return $value === null ? null : Str::upper(trim($value));
    }

    /**
     * Whole pesos: 49 centavos or less drop, 50 or more round up.
     */
    private function peso(float $amount): int
    {
        return (int) round($amount, 0, PHP_ROUND_HALF_UP);
    }
}
