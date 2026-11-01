<?php

namespace App\Withholding\Services;

use App\Profiles\Models\TaxProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * The alphalists of payees in the BIR's data file layout: the quarterly
 * one (QAP) that goes with the 1601-EQ, and the annual one with the
 * 1604-E. Each lists the payee, their ATC, what they were paid and what
 * was withheld.
 *
 * @phpstan-import-type Quarter from WithholdingAgent
 * @phpstan-import-type Annual from WithholdingAgent
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
        return $this->file('1601EQ', $profile, $this->periodEnd($year, $q['quarter'])->format('m/Y'), $q['payees']);
    }

    public function annualFileName(TaxProfile $profile, int $year): ?string
    {
        $tin = $this->digits($profile->tin);

        return $tin === null ? null : strtr((string) config('tax.withholding.alphalist_file_name'), [
            '{tin}' => $tin,
            '{branch}' => (string) config('tax.sawt.branch_code'),
            '{year}' => (string) $year,
        ]);
    }

    /**
     * @param  Annual  $annual
     */
    public function annualDat(TaxProfile $profile, array $annual): string
    {
        return $this->file('1604E', $profile, "12/31/{$annual['year']}", $annual['payees']);
    }

    /**
     * A header, a detail line per payee, and a control line with the totals.
     *
     * @param  list<array{name: string, tin: string|null, atc: string, income: float, withheld: float}>  $payees
     */
    private function file(string $form, TaxProfile $profile, string $period, array $payees): string
    {
        $tin = (string) $this->digits($profile->tin);
        $branch = (string) config('tax.sawt.branch_code');
        $name = $profile->nameParts();
        $atcs = (array) config('tax.withholding.atcs');

        $lines = [implode(',', ["H{$form}", $tin, $branch, $this->text((string) $profile->trade_name),
            $this->text($name['last'] ?? (string) $profile->full_name), $this->text($name['first'] ?? ''), $this->text($name['middle'] ?? ''),
            $period, (string) $profile->rdo_code])];

        foreach ($payees as $i => $payee) {
            $lines[] = implode(',', ["D{$form}", (string) ($i + 1), (string) $this->digits($payee['tin']), $branch, $this->text($payee['name']),
                $this->text(''), $this->text(''), $this->text(''), $period, $payee['atc'],
                $this->amount((float) ($atcs[$payee['atc']]['rate'] ?? 0) * 100), $this->amount($payee['income']), $this->amount($payee['withheld'])]);
        }

        $lines[] = implode(',', ["C{$form}", $tin, $branch, $period,
            $this->amount(array_sum(array_column($payees, 'income'))), $this->amount(array_sum(array_column($payees, 'withheld')))]);

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
