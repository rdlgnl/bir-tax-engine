<?php

namespace App\Letters\Services;

use App\Letters\Models\Letter;
use App\Payroll\Models\Employee;
use App\Profiles\Models\TaxProfile;
use App\Returns\Models\Filing;
use App\Returns\Services\ReturnForm;
use App\Tax\Enums\Regime;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Number;

/**
 * What the taxpayer's own records say about a letter: whether the return
 * it names was filed, wasn't needed, or is missing, and what to send or
 * bring when replying.
 *
 * @phpstan-type Document array{title: string, detail: string, status: string, url: string|null}
 * @phpstan-type Check array{result: 'filed'|'not_needed'|'missing'|'income'|'none', title: string, detail: string, url: string|null, documents: list<Document>}
 */
class LetterCheck
{
    public function __construct(private TaxCalculationService $engine) {}

    /**
     * @return Check
     */
    public function for(Letter $letter, TaxProfile $profile): array
    {
        $checks = config("letters.kinds.{$letter->kind}.checks");

        if ($checks === null || $letter->year === null || ($checks === 'filing' && $letter->form === null)) {
            return ['result' => 'none', 'title' => '', 'detail' => '', 'url' => null, 'documents' => []];
        }

        // The registrations and method the letter's year was taxed on.
        $profile = $profile->forYear($letter->year);

        return $checks === 'income' ? $this->income($letter, $profile) : $this->filing($letter, $profile);
    }

    /**
     * "Q1 2024", "October 2026" or "2024", as the letter names the period.
     */
    public function period(Letter $letter): string
    {
        return match (true) {
            $letter->year === null => '',
            $letter->month !== null => CarbonImmutable::create($letter->year, $letter->month)->format('F Y'),
            $letter->quarter !== null => "Q{$letter->quarter} {$letter->year}",
            default => (string) $letter->year,
        };
    }

    /**
     * @return Check
     */
    private function filing(Letter $letter, TaxProfile $profile): array
    {
        $form = (string) $letter->form;
        $year = (int) $letter->year;
        $period = $this->period($letter);
        $filing = Filing::query()->where('form', $form)->where('year', $year)->where('amendment', 0)
            ->when($letter->quarter !== null, fn ($q) => $q->where('quarter', $letter->quarter))
            ->when($letter->month !== null, fn ($q) => $q->where('month', $letter->month))
            ->first();

        if ($filing !== null) {
            $paid = $filing->amount_paid !== null && $filing->paid_on !== null;

            return [
                'result' => 'filed',
                'title' => 'Your records say you filed it',
                'detail' => "You marked the {$form} for {$period} filed on {$filing->filed_on->format('M j, Y')}"
                    .($filing->reference_number ? " (reference {$filing->reference_number})" : '')
                    .($paid ? ', and paid ₱'.Number::format((float) $filing->amount_paid, maxPrecision: 2).' on '.$filing->paid_on->format('M j, Y') : '')
                    .'. Send proof of both.',
                'url' => $this->historyUrl($filing),
                'documents' => [
                    ['title' => "{$period} {$form}", 'detail' => 'As you filed it', 'status' => 'In History', 'url' => $this->historyUrl($filing)],
                    ['title' => 'Filing confirmation', 'detail' => $filing->reference_number ? "Reference {$filing->reference_number}" : 'BIR email or stamped copy', 'status' => $filing->reference_number ? 'Recorded' : 'Find it', 'url' => null],
                    ['title' => 'Payment proof', 'detail' => $period, 'status' => $paid ? 'Recorded' : 'Find it', 'url' => null],
                ],
            ];
        }

        if (($reason = $this->notNeeded($letter, $profile)) !== null) {
            return $reason;
        }

        $hasRecords = Transaction::query()->whereYear('occurred_on', $year)->exists();
        $canFile = $hasRecords && in_array($form, [...ReturnForm::INCOME_TAX_FORMS, '2551Q', '2550Q'], true) && $letter->quarter !== null;

        return [
            'result' => 'missing',
            'title' => "Your records don't show it filed",
            'detail' => $hasRecords
                ? 'If you filed it outside the app, find your copy and proof of payment. If not, file it now; paying late adds a surcharge and interest.'
                : "There are no {$year} records in the app. If you filed it, find your copy and proof of payment; if not, file it now.",
            'url' => $canFile ? route('returns.show', [$year, $letter->quarter, $form], absolute: false) : null,
            'documents' => [
                ['title' => "{$period} {$form}", 'detail' => 'Your copy, if you filed it', 'status' => 'Find it', 'url' => null],
                ['title' => 'Filing confirmation', 'detail' => 'BIR email or stamped copy', 'status' => 'Find it', 'url' => null],
                ['title' => 'Payment proof', 'detail' => $period, 'status' => 'Find it', 'url' => null],
            ],
        ];
    }

    /**
     * A return the taxpayer didn't have to file, and why.
     *
     * @return Check|null
     */
    private function notNeeded(Letter $letter, TaxProfile $profile): ?array
    {
        $year = (int) $letter->year;
        $period = $this->period($letter);

        if ($letter->form === '2551Q') {
            $q1 = Filing::firstQuarterReturn($year);
            $regime = is_array($q1?->snapshot) ? ($q1->snapshot['regime'] ?? null) : null;

            if ($regime === Regime::EightPercent->value || ($q1 === null && $year === CarbonImmutable::now()->year && $profile->regime === Regime::EightPercent)) {
                return [
                    'result' => 'not_needed',
                    'title' => "Your records say you didn't need it",
                    'detail' => "In {$year} you chose 8%".($q1 ? ' on your Q1 1701Q' : '').'. The 8% covers percentage tax, so 2551Q wasn\'t required.',
                    'url' => $q1 ? $this->historyUrl($q1) : null,
                    'documents' => [
                        ['title' => "Q1 {$year} 1701Q", 'detail' => 'Shows the 8% option', 'status' => $q1 ? 'In History' : 'Find it', 'url' => $q1 ? $this->historyUrl($q1) : null],
                        ['title' => 'Filing confirmation', 'detail' => 'BIR email or stamped copy', 'status' => $q1?->reference_number ? 'Recorded' : 'Find it', 'url' => null],
                        ['title' => 'Payment proof', 'detail' => "Q1 {$year}", 'status' => $q1?->paid_on ? 'Recorded' : 'Find it', 'url' => null],
                    ],
                ];
            }
        }

        $reason = match (true) {
            $letter->form === '2550Q' && ! $profile->vat_registered => "You aren't VAT-registered, so 2550Q isn't required.",
            in_array($letter->form, ['0619-E', '1601-EQ', '1604-E'], true) && ! $profile->withholding_agent => "You aren't a withholding agent, so the {$letter->form} isn't required.",
            in_array($letter->form, ['1601-C', '1604-C'], true) && ! $this->hadEmployees($letter) => "You had no employees for {$period}, so the {$letter->form} isn't required.",
            default => null,
        };

        return $reason === null ? null : [
            'result' => 'not_needed',
            'title' => "Your records say you didn't need it",
            'detail' => $reason.' Check that your BIR registration (COR) agrees.',
            'url' => route('profile', absolute: false),
            'documents' => [
                ['title' => 'Certificate of Registration (COR)', 'detail' => 'The tax types you are registered for', 'status' => 'Find it', 'url' => null],
            ],
        ];
    }

    /**
     * @return Check
     */
    private function income(Letter $letter, TaxProfile $profile): array
    {
        $year = (int) $letter->year;
        $result = $this->engine->forYear($profile, $year);
        $figures = $result['quarters'][$letter->quarter ?? 4];
        $period = $letter->quarter !== null ? "Q{$letter->quarter} {$year}" : (string) $year;
        $sales = $letter->quarter !== null ? $figures['quarter_gross'] : $result['gross'];

        return [
            'result' => 'income',
            'title' => "Your records for {$period}",
            'detail' => 'You recorded ₱'.Number::format($sales, maxPrecision: 2).' in sales'
                .($figures['withheld'] > 0 ? ' and ₱'.Number::format($figures['withheld'], maxPrecision: 2).' withheld by clients so far that year' : '')
                .'. Compare it with the amount in the letter; a client may have reported paying you more.',
            'url' => route('transactions.index', ['year' => $year], absolute: false),
            'documents' => [
                ['title' => "Invoices for {$period}", 'detail' => 'Every sale you recorded', 'status' => 'In Transactions', 'url' => route('transactions.index', ['year' => $year], absolute: false)],
                ['title' => '2307s from clients', 'detail' => 'What each client reported paying you', 'status' => 'In Compliance', 'url' => route('compliance.show', absolute: false)],
            ],
        ];
    }

    private function hadEmployees(Letter $letter): bool
    {
        $months = $letter->month !== null ? [$letter->month] : ($letter->quarter !== null ? range($letter->quarter * 3 - 2, $letter->quarter * 3) : range(1, 12));

        return Employee::query()->get()->contains(fn (Employee $e) => collect($months)->contains(fn (int $m) => $e->workedIn((int) $letter->year, $m)));
    }

    private function historyUrl(Filing $filing): ?string
    {
        return in_array($filing->form, [...ReturnForm::INCOME_TAX_FORMS, '2551Q', '2550Q'], true)
            ? route('history', ['year' => $filing->year, 'quarter' => $filing->quarter, 'form' => $filing->form], absolute: false)
            : null;
    }
}
