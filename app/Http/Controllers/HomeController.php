<?php

namespace App\Http\Controllers;

use App\Compliance\Models\CompensationCertificate;
use App\Compliance\Services\TaxObligations;
use App\Data\Services\Backups;
use App\Notices\Services\Notices;
use App\Profiles\Enums\IncomeSource;
use App\Profiles\Models\TaxProfile;
use App\Tax\Services\RulePacks;
use App\Tax\Services\TaxCalculationService;
use App\Tax\Services\VatCalculator;
use App\Transactions\Models\Transaction;
use App\Transactions\Resources\TransactionResource;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The dashboard. It draws on every domain, so it lives outside them.
 */
class HomeController extends Controller
{
    public function __invoke(TaxCalculationService $engine, TaxObligations $obligations, Backups $backups, Notices $notices, RulePacks $packs, VatCalculator $vatCalculator): Response
    {
        $profile = TaxProfile::current();
        $today = CarbonImmutable::now();
        $year = $engine->forYear($profile, $today->year, $today);
        $next = $obligations->nextPayment($profile, $year, $today);

        // The quarter cards follow the return being paid, else the quarter in progress.
        $focus = $next['quarter'] !== null && $next['year'] === $today->year
            ? $next['quarter']
            : $year['quarters'][$today->quarter];

        $packs->remember($profile, $today);

        $transactions = Transaction::query()->forYear($today->year)->orderBy('occurred_on')->orderBy('id')->get();
        $effects = $engine->transactionEffects($profile, $transactions);

        return Inertia::render('home', [
            'today' => $today->toDateString(),
            'nextPayment' => $next,
            'focus' => $focus,
            'yearStatus' => $year['status'],
            'yearGross' => $year['gross'],
            'regime' => $profile->regime?->value,
            'vatRegistered' => $profile->vat_registered,
            'withholdingAgent' => $profile->withholding_agent,
            // A job alongside the business: the salary's tax is the employer's until the annual return.
            'job' => $profile->income_source === IncomeSource::Both ? [
                'employer' => $profile->employer_name,
                'monthly_salary' => $profile->monthly_salary !== null ? (float) $profile->monthly_salary : null,
                'annual_salary' => $profile->monthly_salary !== null ? round((float) $profile->monthly_salary * 12, 2) : null,
                'employer_withholds' => $profile->monthly_salary !== null ? $engine->graduatedTax((float) $profile->monthly_salary * 12) : null,
                'annual_form' => $year['quarters'][4]['form'],
                'has_2316' => CompensationCertificate::query()->where('year', $today->year)->exists(),
                'annual_due' => $year['quarters'][4]['due_date'],
            ] : null,
            'checklist' => $obligations->checklist($profile, $today->year, $focus, $transactions, $today),
            'upcoming' => $obligations->upcoming($profile, $year, $today),
            'recent' => $transactions
                ->sortBy([['occurred_on', 'desc'], ['id', 'desc']])
                ->take(5)
                ->values()
                ->map(fn (Transaction $t) => [...TransactionResource::make($t)->resolve(), 'tax_effect' => $effects[$t->id]]),
            // The records exist only on this computer, so Home nudges a monthly backup.
            'backupReminder' => $backups->reminder($today),
            'unreadNotices' => $notices->unreadCount($profile, $today),
            'ruleUpdate' => $packs->pending() ? $packs->version() : null,
            // VAT this quarter so far, and how last quarter's return stands.
            'vat' => $profile->vat_registered ? (function () use ($vatCalculator, $today) {
                $year = $vatCalculator->forYear($today->year, $today);
                $previous = $today->quarter === 1 ? $vatCalculator->forYear($today->year - 1, $today)[4] : $year[$today->quarter - 1];

                return ['current' => $year[$today->quarter], 'previous' => $previous, 'year' => $today->year];
            })() : null,
        ]);
    }
}
