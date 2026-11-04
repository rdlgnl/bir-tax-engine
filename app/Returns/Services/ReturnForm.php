<?php

namespace App\Returns\Services;

use App\Profiles\Enums\IncomeSource;
use App\Profiles\Enums\Profession;
use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\OfficialForms;
use App\Returns\Models\Filing;
use App\Tax\Enums\BooksMethod;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Enums\Regime;
use App\Tax\Services\Penalties;
use App\Tax\Services\TaxCalculationService;
use App\Tax\Services\TaxRules;
use App\Tax\Services\VatCalculator;
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
 * @phpstan-import-type Penalty from Penalties
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
 *
 * @phpstan-import-type VatQuarter from VatCalculator
 *
 * @phpstan-type Snapshot array{
 *     form: string,
 *     year: int,
 *     quarter: int,
 *     regime: string|null,
 *     deduction_method: string|null,
 *     books_method?: string,
 *     purely_self_employed: bool,
 *     taxpayer: Taxpayer,
 *     figures: QuarterResult,
 *     previous_figures: QuarterResult|null,
 *     rule_pack: string,
 *     rules: list<string>,
 *     rule_set?: array{date: string, values: array<string, mixed>},
 *     taken_at: string,
 *     penalties: Penalty|null,
 *     amendment: AmendmentInfo|null,
 *     vat?: VatQuarter,
 * }
 * @phpstan-type AmendmentInfo array{number: int, previously_paid: float, original_filed_on: string, on_time: bool}
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
 *     books_method: string,
 *     taxpayer: array{name: string, tin: string|null, rdo: string|null, atc: string},
 *     with_salary: bool,
 *     lines: list<Line>,
 *     payable: float,
 *     penalties: Penalty|null,
 *     amendment: AmendmentInfo|null,
 *     total_payable: float,
 *     overpayment: float,
 *     rule_pack: string,
 *     rules: list<string>,
 *     taken_at: string,
 * }
 */
class ReturnForm
{
    public const INCOME_TAX_FORMS = ['1701Q', '1701-MS', '1701A', '1701'];

    public function __construct(
        private TaxCalculationService $engine,
        private VatCalculator $vat,
        private Penalties $penalties,
        private TaxRules $rules,
    ) {}

    /**
     * The return as it stands now, computed from current records.
     *
     * @return Snapshot
     */
    public function snapshot(TaxProfile $profile, int $year, int $quarter, string $form, ?CarbonImmutable $today = null): array
    {
        $result = $this->engine->forYear($profile, $year, $today);
        // The year's method, and graduated rates once an 8% year passed ₱3,000,000.
        $profile = $this->engine->taxpayerFor($profile, $result, $quarter);
        $name = $profile->nameParts();
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
            'books_method' => $profile->incomeMethod()->value,
            'purely_self_employed' => $profile->isPurelySelfEmployed(),
            'taxpayer' => [
                // As the forms write it: "LAST, FIRST MIDDLE".
                'name' => $name['last'] === null
                    ? Str::upper(trim((string) $profile->full_name))
                    : Str::upper(Str::squish("{$name['last']}, {$name['first']} {$name['middle']}")),
                'tin' => $profile->tin,
                'rdo' => $profile->rdo_code,
                'atc' => match ($form) {
                    '2551Q' => config('tax.atc.percentage_tax'),
                    '2550Q' => config('tax.atc.vat'),
                    default => config('tax.atc.income_tax.'.($eightPercent ? 'eight_percent' : 'graduated').".{$income}"),
                },
                'income' => $income,
                'last_name' => $this->upper($name['last']),
                'first_name' => $this->upper($name['first']),
                'middle_name' => $this->upper($name['middle']),
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
            'rules' => $this->rulesUsed($profile, $year, $quarter, $form, $result['quarters'][$quarter]),
            // The dated rules in force for the period, kept with a filed return.
            'rule_set' => $this->ruleSet($year, $quarter, $form),
            'taken_at' => ($today ?? CarbonImmutable::now())->toIso8601String(),
            'penalties' => $this->latePenalties($result['quarters'][$quarter], $form, $result['gross'], $today ?? CarbonImmutable::now()),
            'amendment' => null,
        ] + ($form === '2550Q' ? ['vat' => $this->vat->forYear($year, $today)[$quarter]] : []);
    }

    /**
     * An amended return for a filed quarter: today's figures, less what was
     * paid on the returns filed before, with only the penalties for the
     * difference.
     *
     * @param  AmendmentInfo  $amendment
     * @param  Penalty|null  $penalties
     * @return Snapshot
     */
    public function amendedSnapshot(TaxProfile $profile, int $year, int $quarter, string $form, array $amendment, ?array $penalties, ?CarbonImmutable $today = null): array
    {
        return [
            ...$this->snapshot($profile, $year, $quarter, $form, $today),
            'amendment' => $amendment,
            'penalties' => $penalties,
        ];
    }

    /**
     * What an income tax return filed today adds for being late: a surcharge
     * and interest on the tax payable, from the due date.
     *
     * @param  QuarterResult  $figures
     * @return Penalty|null
     */
    private function latePenalties(array $figures, string $form, float $yearGross, CarbonImmutable $today): ?array
    {
        if (in_array($form, ['2551Q', '2550Q'], true) || $figures['status'] !== 'overdue' || $figures['due'] <= 0) {
            return null;
        }

        return $this->penalties->late($figures['due'], CarbonImmutable::parse($figures['due_date']), $today, $yearGross);
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
            // Returns filed before salaries were recorded had none, and
            // before the computation was traced, no trace.
            'figures' => $snapshot['figures'] + ['compensation' => null, 'trace' => []],
            'penalties' => $snapshot['penalties'] ?? null,
            'amendment' => $snapshot['amendment'] ?? null,
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
        $vat = $snapshot['vat'] ?? null;
        // Income tax forms are in whole pesos: the amount to pay is the form's.
        $whole = $vat === null && ! $percentageTax ? $this->wholePesos($snapshot) : null;
        $payable = match (true) {
            $vat !== null => $vat['payable'],
            $percentageTax => (float) $f['percentage_tax'],
            $whole !== null => $whole['payable'],
            // An amended return pays only what the earlier returns didn't.
            $snapshot['amendment'] !== null => max(0.0, round($f['due'] - $snapshot['amendment']['previously_paid'], 2)),
            default => $f['due'],
        };

        return [
            'form' => $snapshot['form'],
            'edition' => config("tax.forms.{$snapshot['form']}.edition"),
            'title' => match (true) {
                $vat !== null => 'Quarterly Value-Added Tax Return',
                $percentageTax => 'Quarterly Percentage Tax Return',
                $snapshot['quarter'] === 4 => 'Annual Income Tax Return',
                default => 'Quarterly Income Tax Return',
            },
            'year' => $snapshot['year'],
            'quarter' => $snapshot['quarter'],
            'period' => $snapshot['quarter'] === 4 && $vat === null
                ? (string) $snapshot['year']
                : "{$snapshot['year']} · ".$this->ordinal($snapshot['quarter']),
            'method' => match (true) {
                $vat !== null => '12% VAT',
                $percentageTax => '3% percentage tax',
                $snapshot['regime'] === Regime::EightPercent->value => '8% option',
                $snapshot['deduction_method'] === DeductionMethod::Itemized->value => 'graduated rates, itemized deductions',
                default => 'graduated rates, optional standard deduction',
            },
            'regime' => $snapshot['regime'],
            'deduction_method' => $snapshot['deduction_method'],
            // Returns filed before the books' method was recorded were on invoice dates.
            'books_method' => $snapshot['books_method'] ?? BooksMethod::Accrual->value,
            'taxpayer' => [
                'name' => $snapshot['taxpayer']['name'],
                'tin' => $snapshot['taxpayer']['tin'],
                'rdo' => $snapshot['taxpayer']['rdo'],
                'atc' => $snapshot['taxpayer']['atc'],
            ],
            'with_salary' => $snapshot['taxpayer']['income'] === 'mixed',
            'lines' => match (true) {
                $vat !== null => $this->vatLines($vat),
                $percentageTax => $this->percentageTaxLines($f),
                default => $this->wholeLines($this->incomeTaxLines($snapshot), $whole),
            },
            'payable' => $payable,
            'penalties' => $snapshot['penalties'],
            'amendment' => $snapshot['amendment'],
            'total_payable' => round($payable + ($whole['penalties'] ?? $snapshot['penalties']['total'] ?? 0.0), 2),
            'overpayment' => match (true) {
                $vat !== null => $vat['excess'],
                $percentageTax => 0.0,
                $snapshot['amendment'] !== null => max(0.0, round($snapshot['amendment']['previously_paid'] - $f['due'], 2)),
                default => $f['excess_credit'],
            },
            'rule_pack' => $snapshot['rule_pack'],
            'rules' => $snapshot['rules'],
            'taken_at' => $snapshot['taken_at'],
        ];
    }

    /**
     * The rule set a return was computed on: the year's for income tax, the
     * quarter's for percentage tax and VAT.
     *
     * @return array{date: string, values: array<string, mixed>}
     */
    private function ruleSet(int $year, int $quarter, string $form): array
    {
        $set = in_array($form, ['2551Q', '2550Q'], true) ? $this->rules->forQuarter($year, $quarter) : $this->rules->forYear($year);

        return ['date' => $set->date->toDateString(), 'values' => $set->values()];
    }

    /**
     * Where each whole-peso form has its amount payable, before and after penalties.
     */
    private const PAYABLE_ITEMS = [
        '1701Q' => ['28A', '31'],
        '1701-MS' => ['27A', '30'],
        '1701A' => ['24A', '30'],
        '1701' => ['26A', '32'],
    ];

    /**
     * The amount to pay as a whole-peso form has it: each line rounded,
     * then added up, so it can differ from the exact figure by a peso.
     *
     * @param  Snapshot  $snapshot
     * @return array{payable: float, penalties: float}|null
     */
    private function wholePesos(array $snapshot): ?array
    {
        $items = self::PAYABLE_ITEMS[$snapshot['form']] ?? null;

        if ($items === null || ! config("tax.forms.{$snapshot['form']}.whole_pesos")) {
            return null;
        }

        $values = app(OfficialForms::class)->values($snapshot);
        $payable = (float) ($values[$items[0]] ?? 0);

        return ['payable' => max(0.0, $payable), 'penalties' => (float) ($values[$items[1]] ?? 0) - $payable];
    }

    /**
     * A whole-peso form's lines in whole pesos, its payable lines as the form has them.
     *
     * @param  list<Line>  $lines
     * @param  array{payable: float, penalties: float}|null  $whole
     * @return list<Line>
     */
    private function wholeLines(array $lines, ?array $whole): array
    {
        if ($whole === null) {
            return $lines;
        }

        return array_map(fn (array $line) => [...$line, 'amount' => match ($line['label']) {
            'Tax payable' => $whole['payable'],
            'Total amount payable' => $whole['payable'] + $whole['penalties'],
            default => round($line['amount']),
        }], $lines);
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
        $salary = $annual ? $f['compensation'] : null;

        $lines = $annual
            ? [$this->line('Sales/receipts for the year', $f['gross'])]
            : [
                $this->line('Sales/receipts, this quarter', $f['quarter_gross']),
                $this->line('Sales/receipts, previous quarters', $f['gross'] - $f['quarter_gross']),
            ];

        if ($eightPercent) {
            $lines[] = $this->line(
                $snapshot['taxpayer']['income'] === 'mixed' ? 'Less: allowable reduction (none with a job)' : 'Less: allowable reduction from gross sales',
                $f['exempt_amount'],
                'subtract',
            );
        } else {
            $lines[] = $this->line(
                $snapshot['deduction_method'] === DeductionMethod::Itemized->value
                    ? 'Less: allowable itemized deductions'
                    : 'Less: optional standard deduction (40%)',
                $f['deduction'],
                'subtract',
            );
        }

        $lines[] = $this->line($salary !== null ? 'Taxable business income' : 'Taxable income to date', $f['taxable'], 'total');

        if ($salary !== null) {
            // The salary is taxed at graduated rates; on graduated rates the business is taxed on top of it.
            $lines[] = $this->line($eightPercent ? 'Tax due on business (8%)' : 'Tax due on business, on top of your salary', $f['income_tax']);
            $lines[] = $this->line('Taxable salary'.($salary['employer'] !== null ? " ({$salary['employer']})" : ''), $salary['taxable']);
            $lines[] = $this->line('Tax due on salary (graduated rates)', $salary['tax']);
            $lines[] = $this->line('Total tax due', $f['income_tax'] + $salary['tax'], 'total');
        } else {
            $lines[] = $this->line($eightPercent ? 'Tax due (8%)' : 'Tax due (graduated rates)', $f['income_tax'], 'total');
        }

        if ($annual) {
            $lines[] = $this->line('Less: tax paid in Q1 to Q3 returns', $f['prior_payments'], 'subtract');
            $lines[] = $this->line('Less: creditable tax withheld for the year', $f['withheld'], 'subtract');

            if ($salary !== null) {
                $lines[] = $this->line('Less: tax withheld by your employer (2316)', $salary['withheld'], 'subtract');
            }
        } else {
            $lines[] = $this->line('Less: tax paid in previous quarters', $f['prior_payments'], 'subtract');
            $lines[] = $this->line('Less: creditable tax withheld, previous quarters', $f['withheld'] - $f['quarter_withheld'], 'subtract');
            $lines[] = $this->line('Less: creditable tax withheld, this quarter', $f['quarter_withheld'], 'subtract');
        }

        if (($amendment = $snapshot['amendment']) !== null) {
            $lines[] = $this->line('Less: tax paid in the return previously filed', $amendment['previously_paid'], 'subtract');
            $difference = round($f['due'] - $amendment['previously_paid'], 2);
            $lines[] = $difference < 0
                ? $this->line('Overpayment', -$difference, 'total')
                : $this->line('Tax payable', $difference, 'total');
        } else {
            $lines[] = $f['excess_credit'] > 0 && $f['due'] <= 0
                ? $this->line('Overpayment', $f['excess_credit'], 'total')
                : $this->line('Tax payable', $f['due'], 'total');
        }

        if (($penalty = $snapshot['penalties']) !== null) {
            $lines[] = $this->line(sprintf('Add: surcharge (%s%%)', $this->percent($penalty['surcharge_rate'])), $penalty['surcharge']);
            $lines[] = $this->line(sprintf('Add: interest (%s%% a year, %d %s)', $this->percent($penalty['interest_rate']), $penalty['days'], $penalty['days'] === 1 ? 'day' : 'days'), $penalty['interest']);
            $lines[] = $this->line('Total amount payable', max(0.0, $f['due'] - ($snapshot['amendment']['previously_paid'] ?? 0.0)) + $penalty['total'], 'total');
        }

        return $lines;
    }

    /**
     * @param  QuarterResult  $f
     * @return list<Line>
     */
    private function percentageTaxLines(array $f): array
    {
        $tax = (float) $f['percentage_tax'];
        // The quarter's own rate (1% from July 2020 to June 2023), from its
        // trace; a return filed before traces were kept just says "Tax due".
        $rate = collect($f['trace'])->firstWhere('step', 'percentage_tax')['rate'] ?? null;

        return [
            $this->line('Taxable amount (sales, this quarter)', $f['percentage_tax_base'] ?? $f['quarter_gross']),
            $this->line($rate !== null ? sprintf('Tax due (%s%%)', $this->percent($rate)) : 'Tax due', $tax, 'total'),
            $this->line('Less: tax credits / payments', 0.0, 'subtract'),
            $this->line('Tax payable', $tax, 'total'),
        ];
    }

    /**
     * The 2550Q's computation, as the taxpayer copies it into eBIRForms.
     *
     * @param  VatQuarter  $vat
     * @return list<Line>
     */
    private function vatLines(array $vat): array
    {
        return array_values(array_filter([
            $this->line('VATable sales', $vat['vatable_sales']),
            $vat['zero_rated_sales'] > 0 ? $this->line('Zero-rated sales', $vat['zero_rated_sales']) : null,
            $vat['exempt_sales'] > 0 ? $this->line('Exempt sales', $vat['exempt_sales']) : null,
            $this->line('Output VAT (12%)', $vat['output_vat'], 'total'),
            $vat['carried_over'] > 0 ? $this->line('Less: input VAT carried over from last quarter', $vat['carried_over'], 'subtract') : null,
            $this->line('Domestic purchases', $vat['purchases']),
            $this->line('Less: input VAT on purchases', $vat['input_vat'], 'subtract'),
            $this->line($vat['excess'] > 0 ? 'Excess input VAT, carried over' : 'VAT payable', $vat['excess'] > 0 ? $vat['excess'] : $vat['payable'], 'total'),
        ]));
    }

    /**
     * @param  QuarterResult  $quarter
     * @return list<string>
     */
    private function rulesUsed(TaxProfile $profile, int $year, int $quarterNumber, string $form, array $quarter): array
    {
        if ($form === '2551Q') {
            $id = $this->rules->forQuarter($year, $quarterNumber)->ruleId('percentage_tax');

            return ["{$id} v".config("tax.rules.{$id}.version")];
        }

        if ($form === '2550Q') {
            return ['VAT-12PCT v'.config('tax.rules.VAT-12PCT.version')];
        }

        // The income tax rules the computation applied, from its trace.
        $ids = [...TaxCalculationService::rulesApplied($quarter, 'income_tax'), 'DL-1701Q'];

        return array_map(fn (string $id) => "{$id} v".config("tax.rules.{$id}.version"), $ids);
    }

    private function percent(float $rate): string
    {
        return rtrim(rtrim(number_format($rate * 100, 1), '0'), '.');
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
