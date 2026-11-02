<?php

namespace App\Tax\Services;

use App\Profiles\Models\TaxProfile;
use App\Returns\Models\Filing;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Enums\Regime;
use App\Transactions\Enums\CertificateStatus;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

/**
 * Traces a computed quarter back to the profile answers and BIR rules behind
 * it, for the "Why is this my tax?" panel.
 *
 * @phpstan-import-type QuarterResult from TaxCalculationService
 * @phpstan-import-type Switch from TaxCalculationService
 *
 * @phpstan-type Rule array{id: string, version: int, title: string, basis: string, effective: string, detail: string|null}
 */
class TaxExplainer
{
    public function __construct(private TaxRules $taxRules) {}

    /**
     * @param  QuarterResult  $quarter
     * @param  Collection<int, Transaction>  $transactions  the year's transactions
     * @param  Switch|null  $switched  when an 8% year passed ₱3,000,000
     * @return array{
     *     reasons: list<array{text: string, source: string, applies: bool}>,
     *     rules: list<Rule>,
     *     skipped: list<array{id: string, title: string, reason: string}>,
     *     warnings: list<string>,
     * }
     */
    public function explain(TaxProfile $profile, int $year, array $quarter, Collection $transactions, CarbonImmutable $today, ?array $switched = null): array
    {
        $eightPercent = $profile->regime === Regime::EightPercent;
        $afterSwitch = $switched !== null && $quarter['quarter'] >= $switched['quarter'];
        // What was chosen on the Q1 return: 8%, even once the year left it.
        $chosen = $afterSwitch ? Regime::EightPercent->label() : ($profile->regime?->label() ?? 'No method');
        $reasons = $this->reasons($profile, $year, $quarter, $eightPercent, $chosen);

        if ($afterSwitch) {
            array_splice($reasons, 2, 0, [[
                'text' => 'Sales passed '.$this->peso(config('tax.eight_percent.gross_limit'), short: true).' in '.CarbonImmutable::create($year, $switched['month'])->format('F').', so graduated rates apply from Q'.$switched['quarter'],
                'source' => 'transactions',
                'applies' => true,
            ]]);
        }

        return [
            'reasons' => $reasons,
            'rules' => $this->rules($profile, $year, $quarter, $eightPercent),
            'skipped' => $this->skipped($year, $quarter['quarter'], $eightPercent),
            'warnings' => $this->warnings($year, $quarter, $transactions, $today, $eightPercent),
        ];
    }

    /**
     * @param  QuarterResult  $quarter
     * @return list<array{text: string, source: string, applies: bool}>
     */
    private function reasons(TaxProfile $profile, int $year, array $quarter, bool $eightPercent, string $method): array
    {
        $q1 = Filing::query()
            ->where('year', $year)
            ->where('quarter', 1)
            ->where('form', '1701Q')
            ->first();

        $choice = $q1 !== null
            ? ['text' => "You chose {$method} on your Q1 return", 'source' => 'filed '.$q1->filed_on->format('M j')]
            : ['text' => "You chose {$method} in setup", 'source' => 'locks when Q1 is filed'];

        $reasons = [
            ['text' => 'Individual, self-employed', 'source' => 'your answers', 'applies' => true],
            [
                'text' => $profile->vat_registered ? 'VAT-registered' : 'Not VAT-registered',
                'source' => $profile->tin ? 'your COR' : 'your answers',
                'applies' => ! $profile->vat_registered,
            ],
        ];

        if ($eightPercent) {
            $reasons[] = [
                'text' => $this->peso($quarter['gross']).' so far, under '.$this->peso(config('tax.eight_percent.gross_limit'), short: true),
                'source' => 'transactions',
                'applies' => true,
            ];
        }

        $reasons[] = [...$choice, 'applies' => true];

        if ($eightPercent) {
            $reasons[] = $profile->isPurelySelfEmployed()
                ? ['text' => 'No salary, so the ₱250,000 reduction applies', 'source' => 'your answers', 'applies' => true]
                : ['text' => 'You also earn a salary, so there is no ₱250,000 reduction', 'source' => 'your answers', 'applies' => false];
        } else {
            $reasons[] = [
                'text' => $profile->deduction_method === DeductionMethod::Itemized
                    ? 'Expenses with invoices in your name lower your taxable income'
                    : 'A flat 40% of sales is deducted, no receipts needed',
                'source' => 'your answers',
                'applies' => true,
            ];
        }

        return $reasons;
    }

    /**
     * @param  QuarterResult  $quarter
     * @return list<Rule>
     */
    private function rules(TaxProfile $profile, int $year, array $quarter, bool $eightPercent): array
    {
        // The year's table and the quarter's percentage tax, as the engine used them.
        $ids = $eightPercent
            ? [$profile->isPurelySelfEmployed() ? 'IT-8PCT-PURE' : 'IT-8PCT-MIXED']
            : [
                $this->taxRules->forYear($year)->ruleId('graduated'),
                $profile->deduction_method === DeductionMethod::Itemized ? 'IT-ITEMIZED' : 'IT-OSD',
                $this->taxRules->forQuarter($year, $quarter['quarter'])->ruleId('percentage_tax'),
            ];

        $rules = array_map(fn (string $id) => $this->rule($id), $ids);

        if ($quarter['withheld'] > 0) {
            $rules[] = $this->rule('CWT-CREDIT');
        }

        $statutory = CarbonImmutable::parse($quarter['statutory_date']);
        $due = CarbonImmutable::parse($quarter['due_date']);

        if ($quarter['due_date_moved_reason'] !== null) {
            $rules[] = [
                ...$this->rule('DL-WEEKEND-HOLIDAY'),
                'title' => "Due {$statutory->format('M j')}, moved to {$due->format('D M j')}",
                'basis' => $this->rule('DL-1701Q')['basis'].' · '.$this->rule('DL-WEEKEND-HOLIDAY')['basis'],
                'detail' => 'Rules DL-1701Q, DL-WEEKEND-HOLIDAY',
            ];
        } else {
            $rules[] = [...$this->rule('DL-1701Q'), 'title' => "Due {$due->format('D M j')}"];
        }

        return $rules;
    }

    /**
     * @return list<array{id: string, title: string, reason: string}>
     */
    private function skipped(int $year, int $quarter, bool $eightPercent): array
    {
        if ($eightPercent) {
            $percentageTax = $this->taxRules->forQuarter($year, $quarter);

            return [
                ['id' => $percentageTax->ruleId('percentage_tax'), 'title' => 'Percentage tax '.round($percentageTax->rate('percentage_tax_rate') * 100).'%', 'reason' => 'skipped because you chose 8%, which replaces it.'],
                ['id' => $this->taxRules->forYear($year)->ruleId('graduated'), 'title' => 'Graduated rates', 'reason' => 'skipped because you chose 8%.'],
            ];
        }

        return [
            ['id' => 'IT-8PCT-PURE', 'title' => 'Flat 8%', 'reason' => 'skipped because you chose graduated rates.'],
        ];
    }

    /**
     * @param  QuarterResult  $quarter
     * @param  Collection<int, Transaction>  $transactions
     * @return list<string>
     */
    private function warnings(int $year, array $quarter, Collection $transactions, CarbonImmutable $today, bool $eightPercent): array
    {
        $warnings = $transactions
            ->filter(fn (Transaction $t) => $t->quarter() <= $quarter['quarter']
                && $t->certificateStatus($today) === CertificateStatus::Missing)
            ->groupBy('counterparty')
            ->map(fn (Collection $missing, string $client) => 'No 2307 yet from '.rtrim($client, '.').'. Without it, '
                .$this->peso((float) $missing->sum('tax_withheld')).' of your credit may be disallowed.')
            ->values()
            ->all();

        if ($eightPercent) {
            $warnings[] = "Passing ₱3,000,000 this year ends the 8% option for all of {$year}.";
        }

        return array_values($warnings);
    }

    /**
     * @return Rule
     */
    private function rule(string $id): array
    {
        $rule = config("tax.rules.{$id}");

        return [
            'id' => $id,
            'version' => $rule['version'],
            'title' => $rule['title'],
            'basis' => $rule['basis'],
            'effective' => $rule['effective'],
            'detail' => null,
        ];
    }

    private function peso(float|int $amount, bool $short = false): string
    {
        if ($short && $amount >= 1_000_000) {
            return '₱'.Number::format($amount / 1_000_000, maxPrecision: 1).'M';
        }

        return '₱'.Number::format($amount, maxPrecision: 2);
    }
}
