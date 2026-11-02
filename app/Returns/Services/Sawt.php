<?php

namespace App\Returns\Services;

use App\Compliance\Models\Certificate;
use App\Profiles\Models\TaxProfile;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * The Summary Alphalist of Withholding Taxes for one quarter: every client
 * who withheld tax, with the income and tax on their 2307. It goes with the
 * income tax return that claims those credits.
 *
 * @phpstan-type SawtRow array{seq: int, payor: string, tin: string|null, atc: string|null, income: float, rate: float, withheld: float, certificate: bool}
 */
class Sawt
{
    public function __construct(private TaxCalculationService $engine) {}

    /**
     * One row per client, alphabetically, named and coded as on their 2307
     * when it has been added.
     *
     * @return list<SawtRow>
     */
    public function rows(int $year, int $quarter): array
    {
        $groups = Transaction::query()
            ->forQuarter($year, $quarter)
            ->where('tax_withheld', '>', 0)
            ->with('certificate')
            ->orderBy('counterparty')
            ->get()
            ->groupBy('counterparty');

        $rows = [];

        foreach ($groups as $client => $payments) {
            $certificate = $payments->pluck('certificate')->filter()->first();
            $income = round((float) $payments->sum('amount'), 2);
            $withheld = round((float) $payments->sum('tax_withheld'), 2);
            $onFile = $certificate instanceof Certificate;
            // The 2307 is in once every payment it covers is marked received, as on Compliance.
            $received = $payments->every(fn (Transaction $t) => $t->certificate_received_on !== null);

            $rows[] = [
                'seq' => count($rows) + 1,
                'payor' => $onFile ? $certificate->payor_name : (string) $client,
                'tin' => $onFile ? $certificate->payor_tin : null,
                'atc' => $onFile ? $certificate->atc_code : null,
                'income' => $income,
                'rate' => $income > 0 ? round($withheld / $income * 100, 2) : 0.0,
                'withheld' => $withheld,
                'certificate' => $received,
            ];
        }

        return $rows;
    }

    /**
     * The return the SAWT is attached to: the 1701Q, or the annual return
     * for the fourth quarter.
     */
    public function form(TaxProfile $profile, int $year, int $quarter): string
    {
        return $this->engine->forYear($profile, $year)['quarters'][$quarter]['form'];
    }

    /**
     * The file name the BIR expects, or null without the taxpayer's TIN.
     */
    public function fileName(TaxProfile $profile, int $year, int $quarter): ?string
    {
        $tin = $this->tin($profile);

        if ($tin === null) {
            return null;
        }

        return strtr(config('tax.sawt.file_name'), [
            '{tin}' => $tin,
            '{branch}' => config('tax.sawt.branch_code'),
            '{period}' => $this->periodEnd($year, $quarter)->format('mY'),
            '{form}' => $this->form($profile, $year, $quarter),
        ]);
    }

    /**
     * The data file in the alphalist layout: a header, a detail line per
     * client, and a control line with the totals.
     */
    public function dat(TaxProfile $profile, int $year, int $quarter): string
    {
        $tin = (string) $this->tin($profile);
        $branch = config('tax.sawt.branch_code');
        $form = $this->form($profile, $year, $quarter);
        $period = $this->periodEnd($year, $quarter)->format('m/Y');
        $name = $profile->nameParts();
        $rows = $this->rows($year, $quarter);

        $lines = [$this->line([
            'HSAWT', "H{$form}", $tin, $branch,
            $this->text($profile->trade_name ?? ''),
            $this->text($name['last'] ?? $profile->full_name ?? ''),
            $this->text($name['first'] ?? ''),
            $this->text($name['middle'] ?? ''),
            $period, (string) $profile->rdo_code,
        ])];

        foreach ($rows as $row) {
            $payorTin = substr(preg_replace('/\D/', '', (string) $row['tin']), 0, 9);
            $payorBranch = substr(preg_replace('/\D/', '', (string) $row['tin']), 9) ?: '';

            $lines[] = $this->line([
                'DSAWT', "D{$form}", (string) $row['seq'], $payorTin, str_pad($payorBranch, 4, '0', STR_PAD_LEFT),
                // A client is listed by registered name; the individual name fields stay empty.
                $this->text($row['payor']), $this->text(''), $this->text(''), $this->text(''),
                $period, (string) $row['atc'], $this->amount($row['rate']),
                $this->amount($row['income']), $this->amount($row['withheld']),
            ]);
        }

        $lines[] = $this->line([
            'CSAWT', "C{$form}", $tin, $branch, $period,
            $this->amount(array_sum(array_column($rows, 'income'))),
            $this->amount(array_sum(array_column($rows, 'withheld'))),
        ]);

        return implode("\r\n", $lines)."\r\n";
    }

    private function tin(TaxProfile $profile): ?string
    {
        $digits = substr(preg_replace('/\D/', '', (string) $profile->tin), 0, 9);

        return strlen($digits) === 9 ? $digits : null;
    }

    private function periodEnd(int $year, int $quarter): CarbonImmutable
    {
        return CarbonImmutable::create($year, $quarter * 3)->endOfMonth();
    }

    /**
     * @param  list<string>  $fields
     */
    private function line(array $fields): string
    {
        return implode(',', $fields);
    }

    /**
     * Names go in double quotes, upper case, without characters that would
     * break the line.
     */
    private function text(string $value): string
    {
        $clean = Str::of($value)->replaceMatches('/[",\r\n]+/', ' ')->squish()->upper()->value();

        return "\"{$clean}\"";
    }

    private function amount(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
