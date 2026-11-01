<?php

namespace App\Returns\Forms\Items;

use App\Profiles\Models\TaxProfile;
use App\Withholding\Services\WithholdingAgent;

/**
 * BIR Form 1601-EQ: the quarter's expanded withholding, one line per ATC,
 * less what the 0619-Es of its first two months remitted.
 *
 * @phpstan-import-type Values from FormItems
 * @phpstan-import-type Quarter from WithholdingAgent
 */
class Form1601EQItems extends ProfileFormItems
{
    /**
     * @param  Quarter  $q
     * @return Values
     */
    public function values(TaxProfile $profile, int $year, array $q): array
    {
        $values = $this->identity($profile, [
            'tin' => ['tin.1', 'tin.2', 'tin.3'],
            'rdo' => '7',
            'name' => '8',
            'address' => ['9.1', 40, '9.2'],
            'zip' => '9A',
            'contact' => '10',
            'email' => '12',
        ]) + [
            '1' => (string) $year,
            "2.{$q['quarter']}" => true,
            '3.no' => true,
            '4.yes' => $q['withheld'] > 0,
            '4.no' => $q['withheld'] <= 0,
            '11.private' => true,
        ];

        // Six ATC lines on the form; a seventh would need the attached sheet.
        foreach (array_slice($q['atcs'], 0, 6) as $i => $row) {
            $line = 13 + $i;
            $values += [
                "{$line}.atc" => $row['atc'],
                "{$line}.base" => $row['base'],
                "{$line}.rate" => rtrim(rtrim(number_format($row['rate'] * 100, 2), '0'), '.').'%',
                "{$line}.withheld" => $row['withheld'],
            ];
        }

        $first = $q['months'][0]['status'] === 'filed' ? ($q['months'][0]['amount_paid'] ?? $q['months'][0]['withheld']) : 0.0;
        $second = $q['months'][1]['status'] === 'filed' ? ($q['months'][1]['amount_paid'] ?? $q['months'][1]['withheld']) : 0.0;

        return $values + [
            '19' => $q['withheld'],
            '20' => $first,
            '21' => $second,
            '25' => $q['remitted'],
            '26' => $q['still_due'],
            '31' => $q['still_due'],
        ];
    }
}
