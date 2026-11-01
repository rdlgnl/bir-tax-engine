<?php

namespace App\Withholding\Services;

use App\Profiles\Models\TaxProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * The quarterly alphalist of payees (QAP) that goes with the 1601-EQ: each
 * payee, their ATC, what they were paid and what was withheld.
 *
 * @phpstan-import-type Quarter from WithholdingAgent
 */
class Qap
{
    public function fileName(TaxProfile $profile, int $year, int $quarter): ?string
    {
        $tin = $this->digits($profile->tin);

        return $tin === null ? null : strtr((string) config('tax.withholding.qap_file_name'), [
            '{tin}' => $tin,
            '{branch}' => (string) config('tax.sawt.branch_code'),
            '{period}' => $this->periodEnd($year, $quarter)->format('mY'),
        ]);
    }

    /**
     * @param  Quarter  $q
     */
    public function dat(TaxProfile $profile, int $year, array $q): string
    {
        $tin = (string) $this->digits($profile->tin);
        $branch = (string) config('tax.sawt.branch_code');
        $period = $this->periodEnd($year, $q['quarter'])->format('m/Y');
        $name = $profile->nameParts();
        $atcs = (array) config('tax.withholding.atcs');

        $lines = [implode(',', ['H1601EQ', $tin, $branch, $this->text((string) $profile->trade_name),
            $this->text($name['last'] ?? (string) $profile->full_name), $this->text($name['first'] ?? ''), $this->text($name['middle'] ?? ''),
            $period, (string) $profile->rdo_code])];

        foreach ($q['payees'] as $i => $payee) {
            $lines[] = implode(',', ['D1601EQ', (string) ($i + 1), (string) $this->digits($payee['tin']), $branch, $this->text($payee['name']),
                $this->text(''), $this->text(''), $this->text(''), $period, $payee['atc'],
                $this->amount((float) ($atcs[$payee['atc']]['rate'] ?? 0) * 100), $this->amount($payee['income']), $this->amount($payee['withheld'])]);
        }

        $lines[] = implode(',', ['C1601EQ', $tin, $branch, $period,
            $this->amount(array_sum(array_column($q['payees'], 'income'))), $this->amount(array_sum(array_column($q['payees'], 'withheld')))]);

        return implode("\r\n", $lines)."\r\n";
    }

    private function digits(?string $tin): ?string
    {
        $digits = substr((string) preg_replace('/\D/', '', (string) $tin), 0, 9);

        return strlen($digits) === 9 ? $digits : null;
    }

    private function periodEnd(int $year, int $quarter): CarbonImmutable
    {
        return CarbonImmutable::create($year, $quarter * 3)->endOfMonth();
    }

    private function text(string $value): string
    {
        return '"'.Str::of($value)->replaceMatches('/[",\r\n]+/', ' ')->squish()->upper()->value().'"';
    }

    private function amount(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
