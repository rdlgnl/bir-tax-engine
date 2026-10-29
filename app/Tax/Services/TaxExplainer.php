<?php

namespace App\Tax\Services;

use App\Compliance\Models\Filing;
use App\Profiles\Models\TaxProfile;
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
 *
 * @phpstan-type Rule array{id: string, version: int, title: string, basis: string, effective: string, detail: string|null}
 */
class TaxExplainer
{
    /**
     * @param  QuarterResult  $quarter
     * @param  Collection<int, Transaction>  $transactions  the year's transactions
     * @return array{
     *     reasons: list<array{text: string, source: string, applies: bool}>,
     *     rules: list<Rule>,
     *     skipped: list<array{id: string, title: string, reason: string}>,
     *     warnings: list<string>,
     * }
     */
    public function explain(TaxProfile $profile, int $year, array $quarter, Collection $transactions, CarbonImmutable $today): array
    {
        $eightPercent = $profile->regime === Regime::EightPercent;

        return [
            'reasons' => $this->reasons($profile, $year, $quarter, $eightPercent),
            'rules' => $this->rules($profile, $quarter, $eightPercent),
            'skipped' => $this->skipped($eightPercent),
            'warnings' => $this->warnings($year, $quarter, $transactions, $today, $eightPercent),
        ];
    }

    /**
     * @param  QuarterResult  $quarter
     * @return list<array{text: string, source: string, applies: bool}>
     */
    private function reasons(TaxProfile $profile, int $year, array $quarter, bool $eightPercent): array
    {
        $q1 = Filing::query()
            ->where('year', $year)
            ->where('quarter', 1)
            ->where('form', '1701Q')
            ->first();

        $method = $profile->regime?->label() ?? 'No method';
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
    private function rules(TaxProfile $profile, array $quarter, bool $eightPercent): array
    {
        $ids = $eightPercent
            ? [$profile->isPurelySelfEmployed() ? 'IT-8PCT-PURE' : 'IT-8PCT-MIXED']
            : ['IT-GRAD-2023', $profile->deduction_method === DeductionMethod::Itemized ? 'IT-ITEMIZED' : 'IT-OSD', 'PT-3PCT'];

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
    private function skipped(bool $eightPercent): array
    {
        if ($eightPercent) {
            return [
                ['id' => 'PT-3PCT', 'title' => 'Percentage tax 3%', 'reason' => 'skipped because you chose 8%, which replaces it.'],
                ['id' => 'IT-GRAD-2023', 'title' => 'Graduated rates', 'reason' => 'skipped because you chose 8%.'],
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
