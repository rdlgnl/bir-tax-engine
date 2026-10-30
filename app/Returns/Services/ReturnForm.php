<?php

namespace App\Returns\Services;

use App\Profiles\Enums\IncomeSource;
use App\Profiles\Enums\Profession;
use App\Profiles\Models\TaxProfile;
use App\Returns\Models\Filing;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Enums\Regime;
use App\Tax\Services\TaxCalculationService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Lays out a return, line by line, the way the BIR form asks for it.
 *
 * A return is built from a snapshot: the profile and the computed figures
 * at one moment. Unfiled returns use a fresh snapshot; filed returns use
 * the one frozen when they were marked filed, so they never change.
 *
 * @phpstan-import-type QuarterResult from TaxCalculationService
 *
 * @phpstan-type Taxpayer array{
 *     name: string,
 *     tin: string|null,
 *     rdo: string|null,
 *     atc: string,
 *     income: 'business'|'profession'|'mixed',
 *     last_name: string|null,
 *     first_name: string|null,
 *     middle_name: string|null,
 *     address: string|null,
 *     zip: string|null,
 *     birth_date: string|null,
 *     email: string|null,
 *     contact: string|null,
 *     citizenship: string|null,
 * }
 * @phpstan-type Snapshot array{
 *     form: string,
 *     year: int,
 *     quarter: int,
 *     regime: string|null,
 *     deduction_method: string|null,
 *     purely_self_employed: bool,
 *     taxpayer: Taxpayer,
 *     figures: QuarterResult,
 *     previous_figures: QuarterResult|null,
 *     rule_pack: string,
 *     rules: list<string>,
 *     taken_at: string,
 * }
 * @phpstan-type LegacySnapshot array{
 *     form: string,
 *     year: int,
 *     quarter: int,
 *     regime: string|null,
 *     deduction_method: string|null,
 *     purely_self_employed: bool,
 *     taxpayer: array{name: string, tin: string|null, rdo: string|null, atc: string},
 *     figures: QuarterResult,
 *     rule_pack: string,
 *     rules: list<string>,
 *     taken_at: string,
 * }
 * @phpstan-type Line array{label: string, amount: float, kind: 'amount'|'subtract'|'total'}
 * @phpstan-type Layout array{
 *     form: string,
 *     edition: string,
 *     title: string,
 *     year: int,
 *     quarter: int,
 *     period: string,
 *     method: string,
 *     taxpayer: array{name: string, tin: string|null, rdo: string|null, atc: string},
 *     lines: list<Line>,
 *     payable: float,
 *     overpayment: float,
 *     rule_pack: string,
 *     rules: list<string>,
 *     taken_at: string,
 * }
 */
class ReturnForm
{
    public const INCOME_TAX_FORMS = ['1701Q', '1701A', '1701'];

    public function __construct(private TaxCalculationService $engine) {}

    /**
     * The return as it stands now, computed from current records.
     *
     * @return Snapshot
     */
    public function snapshot(TaxProfile $profile, int $year, int $quarter, string $form, ?CarbonImmutable $today = null): array
    {
        $result = $this->engine->forYear($profile, $year, $today);
        $eightPercent = $profile->regime === Regime::EightPercent;
        $income = match (true) {
            $profile->income_source === IncomeSource::Both => 'mixed',
            $profile->profession !== null && $profile->profession !== Profession::None => 'profession',
            default => 'business',
        };

        return [
            'form' => $form,
            'year' => $year,
            'quarter' => $quarter,
            'regime' => $profile->regime?->value,
            'deduction_method' => $profile->deduction_method?->value,
            'purely_self_employed' => $profile->isPurelySelfEmployed(),
            'taxpayer' => [
                'name' => Str::upper(trim((string) $profile->full_name)),
                'tin' => $profile->tin,
                'rdo' => $profile->rdo_code,
                'atc' => $form === '2551Q'
                    ? config('tax.atc.percentage_tax')
                    : config('tax.atc.income_tax.'.($eightPercent ? 'eight_percent' : 'graduated').".{$income}"),
                'income' => $income,
                'last_name' => $this->upper($profile->last_name),
                'first_name' => $this->upper($profile->first_name),
                'middle_name' => $this->upper($profile->middle_name),
                'address' => $this->upper($profile->registered_address),
                'zip' => $profile->zip_code,
                'birth_date' => $profile->birth_date?->toDateString(),
                'email' => $this->upper($profile->email),
                'contact' => $profile->contact_number,
                'citizenship' => $this->upper($profile->citizenship),
            ],
            'figures' => $result['quarters'][$quarter],
            'previous_figures' => $result['quarters'][$quarter - 1] ?? null,
            'rule_pack' => config('tax.version'),
            'rules' => $this->rulesUsed($profile, $form, $result['quarters'][$quarter]),
            'taken_at' => ($today ?? CarbonImmutable::now())->toIso8601String(),
        ];
    }

    /**
     * A filed return as it was filed, or (for returns filed before snapshots
     * existed) as it computes today.
     *
     * @return Snapshot
     */
    public function snapshotOf(Filing $filing, TaxProfile $profile): array
    {
        $now = $this->snapshot($profile, $filing->year, $filing->quarter, $filing->form);
        $snapshot = $filing->snapshot;

        if ($snapshot === null) {
            return $now;
        }

        // Snapshots frozen before returns were filled on the official forms
        // lack the details those forms ask for: take them from today's records.
        return [
            ...$snapshot,
            'taxpayer' => $snapshot['taxpayer'] + $now['taxpayer'],
            'previous_figures' => $snapshot['previous_figures'] ?? $now['previous_figures'],
        ];
    }

    /**
     * @param  Snapshot  $snapshot
     * @return Layout
     */
    public function layout(array $snapshot): array
    {
        $f = $snapshot['figures'];
        $percentageTax = $snapshot['form'] === '2551Q';
        $payable = $percentageTax ? (float) $f['percentage_tax'] : $f['due'];

        return [
            'form' => $snapshot['form'],
            'edition' => config("tax.forms.{$snapshot['form']}.edition"),
            'title' => match (true) {
                $percentageTax => 'Quarterly Percentage Tax Return',
                $snapshot['quarter'] === 4 => 'Annual Income Tax Return',
                default => 'Quarterly Income Tax Return',
            },
            'year' => $snapshot['year'],
            'quarter' => $snapshot['quarter'],
            'period' => $snapshot['quarter'] === 4
                ? (string) $snapshot['year']
                : "{$snapshot['year']} · ".$this->ordinal($snapshot['quarter']),
            'method' => match (true) {
                $percentageTax => '3% percentage tax',
                $snapshot['regime'] === Regime::EightPercent->value => '8% option',
                $snapshot['deduction_method'] === DeductionMethod::Itemized->value => 'graduated rates, itemized deductions',
                default => 'graduated rates, optional standard deduction',
            },
            'taxpayer' => [
                'name' => $snapshot['taxpayer']['name'],
                'tin' => $snapshot['taxpayer']['tin'],
                'rdo' => $snapshot['taxpayer']['rdo'],
                'atc' => $snapshot['taxpayer']['atc'],
            ],
            'lines' => $percentageTax ? $this->percentageTaxLines($f) : $this->incomeTaxLines($snapshot),
            'payable' => $payable,
            'overpayment' => $percentageTax ? 0.0 : $f['excess_credit'],
            'rule_pack' => $snapshot['rule_pack'],
            'rules' => $snapshot['rules'],
            'taken_at' => $snapshot['taken_at'],
        ];
    }

    /**
     * @param  Snapshot  $snapshot
     * @return list<Line>
     */
    private function incomeTaxLines(array $snapshot): array
    {
        $f = $snapshot['figures'];
        $eightPercent = $snapshot['regime'] === Regime::EightPercent->value;
        $annual = $snapshot['quarter'] === 4;

        $lines = $annual
            ? [$this->line('Sales/receipts for the year', $f['gross'])]
            : [
                $this->line('Sales/receipts, this quarter', $f['quarter_gross']),
                $this->line('Sales/receipts, previous quarters', $f['gross'] - $f['quarter_gross']),
            ];

        if ($eightPercent) {
            $lines[] = $this->line('Less: allowable reduction from gross sales', $f['exempt_amount'], 'subtract');
        } else {
            $lines[] = $this->line(
                $snapshot['deduction_method'] === DeductionMethod::Itemized->value
                    ? 'Less: allowable itemized deductions'
                    : 'Less: optional standard deduction (40%)',
                $f['deduction'],
                'subtract',
            );
        }

        $lines[] = $this->line('Taxable income to date', $f['taxable'], 'total');
        $lines[] = $this->line($eightPercent ? 'Tax due (8%)' : 'Tax due (graduated rates)', $f['income_tax'], 'total');

        if ($annual) {
            $lines[] = $this->line('Less: tax paid in Q1 to Q3 returns', $f['prior_payments'], 'subtract');
            $lines[] = $this->line('Less: creditable tax withheld for the year', $f['withheld'], 'subtract');
        } else {
            $lines[] = $this->line('Less: tax paid in previous quarters', $f['prior_payments'], 'subtract');
            $lines[] = $this->line('Less: creditable tax withheld, previous quarters', $f['withheld'] - $f['quarter_withheld'], 'subtract');
            $lines[] = $this->line('Less: creditable tax withheld, this quarter', $f['quarter_withheld'], 'subtract');
        }

        $lines[] = $f['excess_credit'] > 0 && $f['due'] <= 0
            ? $this->line('Overpayment', $f['excess_credit'], 'total')
            : $this->line('Tax payable', $f['due'], 'total');

        return $lines;
    }

    /**
     * @param  QuarterResult  $f
     * @return list<Line>
     */
    private function percentageTaxLines(array $f): array
    {
        $tax = (float) $f['percentage_tax'];

        return [
            $this->line('Taxable amount (sales, this quarter)', $f['quarter_gross']),
            $this->line('Tax due (3%)', $tax, 'total'),
            $this->line('Less: tax credits / payments', 0.0, 'subtract'),
            $this->line('Tax payable', $tax, 'total'),
        ];
    }

    /**
     * @param  QuarterResult  $quarter
     * @return list<string>
     */
    private function rulesUsed(TaxProfile $profile, string $form, array $quarter): array
    {
        if ($form === '2551Q') {
            return ['PT-3PCT v'.config('tax.rules.PT-3PCT.version')];
        }

        $ids = $profile->regime === Regime::EightPercent
            ? [$profile->isPurelySelfEmployed() ? 'IT-8PCT-PURE' : 'IT-8PCT-MIXED']
            : ['IT-GRAD-2023', $profile->deduction_method === DeductionMethod::Itemized ? 'IT-ITEMIZED' : 'IT-OSD'];

        if ($quarter['withheld'] > 0) {
            $ids[] = 'CWT-CREDIT';
        }

        $ids[] = 'DL-1701Q';

        return array_map(fn (string $id) => "{$id} v".config("tax.rules.{$id}.version"), $ids);
    }

    /**
     * @param  'amount'|'subtract'|'total'  $kind
     * @return Line
     */
    private function line(string $label, float $amount, string $kind = 'amount'): array
    {
        return ['label' => $label, 'amount' => round($amount, 2), 'kind' => $kind];
    }

    private function upper(?string $value): ?string
    {
        return filled($value) ? Str::upper(trim((string) $value)) : null;
    }

    private function ordinal(int $quarter): string
    {
        return [1 => '1st', 2 => '2nd', 3 => '3rd', 4 => '4th'][$quarter];
    }
}
