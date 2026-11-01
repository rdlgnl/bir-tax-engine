<?php

namespace App\Http\Middleware;

use App\Compliance\Services\TaxObligations;
use App\Data\Models\Setting;
use App\Data\Services\AppLock;
use App\Profiles\Enums\WorkType;
use App\Profiles\Models\TaxProfile;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Enums\TransactionType;
use App\Transactions\Models\Transaction;
use App\Transactions\Resources\TransactionResource;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'taxpayer' => fn () => $this->taxpayer(),
            'nextDeadline' => fn () => $this->nextDeadline(),
            'transactionForm' => fn () => $this->transactionForm(),
            // For the browser: lock after time away, and deadline notifications.
            'appLock' => fn () => app(AppLock::class)->enabled() ? ['lock_after' => app(AppLock::class)->lockAfter()] : null,
            'reminders' => fn () => (bool) Setting::get('reminders', false),
        ];
    }

    /**
     * What the add-transaction form needs. It opens from every page, so it
     * is shared rather than passed by one controller.
     *
     * @return array<string, mixed>|null
     */
    private function transactionForm(): ?array
    {
        $profile = TaxProfile::current();

        if (! $profile?->isComplete()) {
            return null;
        }

        return [
            'regime' => $profile->regime?->value,
            'expectedWithholdingPercent' => (int) round(100 * config(match (true) {
                $profile->work_type === WorkType::Products => 'tax.expected_withholding.products',
                // VAT-registered professionals have 10% withheld, sworn declaration or not.
                $profile->vat_registered => 'tax.expected_withholding.services_without_declaration',
                default => 'tax.expected_withholding.services',
            })),
            'vatRate' => $profile->vat_registered ? (float) config('tax.vat.rate') : null,
            // A withholding agent picks the kind of payment; the ATC and rate follow.
            'withholdingAtcs' => $profile->withholding_agent
                ? collect((array) config('tax.withholding.atcs'))->map(fn (array $atc, string $code) => ['code' => $code, ...$atc])->values()
                : null,
            'recentCounterparties' => Transaction::query()
                ->where('type', '!=', TransactionType::Expense)
                ->latest('occurred_on')
                ->limit(30)
                ->pluck('counterparty')
                ->unique()
                ->take(3)
                ->values(),
            'awaitingCertificates' => Transaction::query()
                ->where('tax_withheld', '>', 0)
                ->whereNull('certificate_received_on')
                ->where('occurred_on', '>=', now()->subYear()->startOfYear())
                ->orderBy('occurred_on')
                ->get()
                ->map(fn (Transaction $t) => TransactionResource::make($t)->resolve()),
        ];
    }

    /**
     * @return array{first_name: string, full_name: string|null, initials: string}|null
     */
    private function taxpayer(): ?array
    {
        $profile = TaxProfile::current();

        if (! $profile?->isComplete()) {
            return null;
        }

        return [
            'first_name' => $profile->firstName(),
            'full_name' => $profile->full_name,
            'initials' => $profile->initials(),
        ];
    }

    /**
     * The sidebar's "next deadline" box.
     *
     * @return array{date: string, form: string, quarter: int, amount: float|null}|null
     */
    private function nextDeadline(): ?array
    {
        $profile = TaxProfile::current();

        if (! $profile?->isComplete()) {
            return null;
        }

        $today = CarbonImmutable::now();
        $year = app(TaxCalculationService::class)->forYear($profile, $today->year, $today);
        $quarter = app(TaxObligations::class)->nextPayment($profile, $year, $today)['quarter']
            ?? collect($year['quarters'])->firstWhere('status', 'open');

        return $quarter ? [
            'date' => $quarter['due_date'],
            'form' => $quarter['form'],
            'quarter' => $quarter['quarter'],
            'amount' => $quarter['status'] === 'open' ? null : $quarter['due'],
        ] : null;
    }
}
