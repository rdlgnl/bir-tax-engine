<?php

namespace App\Returns\Forms\Items;

use App\Profiles\Models\TaxProfile;
use App\Withholding\Services\WithholdingAgent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * BIR Form 1604-E: the year's expanded withholding. Schedule 1 has a line
 * per quarter's 1601-EQ; page 2 has the first lines of the alphalist, whose
 * full list goes in the data file.
 *
 * @phpstan-import-type Values from FormItems
 * @phpstan-import-type Annual from WithholdingAgent
 */
class Form1604EItems extends ProfileFormItems
{
    /** Alphalist lines printed on page 2. */
    private const PAYEE_LINES = 3;

    /**
     * @param  Annual  $annual
     * @return Values
     */
    public function values(TaxProfile $profile, array $annual): array
    {
        [$tin1, $tin2, $tin3] = $this->tin($profile);
        $values = $this->identity($profile, [
            'tin' => ['tin.1', 'tin.2', 'tin.3'],
            'rdo' => '5',
            'name' => '6',
            'address' => ['7.1', 40, '7.2'],
            'zip' => '7A',
            'contact' => '9',
            'email' => '10',
        ]) + [
            '1' => substr((string) $annual['year'], -2),
            '2.no' => true,
            'tin.branch' => '00000',
            '8.private' => true,
            '8A.no' => true,
            'p2.tin' => $tin1.$tin2.$tin3 !== '' ? $tin1.$tin2.$tin3.'00000' : null,
        ];
        // Page 2 repeats the agent's name.
        $values['p2.name'] = $this->name($profile);

        $total = 0.0;

        foreach ($annual['quarters'] as $q) {
            if ($q['withheld'] <= 0) {
                continue;
            }

            $n = $q['quarter'];
            $total += $q['withheld'];
            $values += [
                // Dated once the quarter's 1601-EQ is filed.
                "s1.{$n}.date" => $q['filed_on'] !== null ? CarbonImmutable::parse($q['filed_on'])->format('m/d/Y') : null,
                "s1.{$n}.withheld" => $q['withheld'],
                "s1.{$n}.total" => $q['withheld'],
            ];
        }

        foreach (array_slice($annual['payees'], 0, self::PAYEE_LINES) as $i => $payee) {
            $n = $i + 1;
            $values += [
                "s3.{$n}.seq" => (string) $n,
                "s3.{$n}.tin" => $payee['tin'],
                "s3.{$n}.name" => Str::upper($payee['name']),
                "s3.{$n}.atc" => $payee['atc'],
                "s3.{$n}.income" => $payee['income'],
                "s3.{$n}.rate" => rtrim(rtrim(number_format($payee['rate'] * 100, 2), '0'), '.').'%',
                "s3.{$n}.withheld" => $payee['withheld'],
            ];
        }

        return $values + [
            's1.total.withheld' => round($total, 2),
            's1.total.total' => round($total, 2),
            's3.total.income' => $annual['income'],
            's3.total.withheld' => round(array_sum(array_column($annual['payees'], 'withheld')), 2),
        ];
    }
}
