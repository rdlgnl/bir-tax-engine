<?php

namespace App\Http\Controllers;

use App\Compliance\Services\TaxObligations;
use App\Profiles\Models\TaxProfile;
use App\Tax\Services\TaxCalculationService;
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
    public function __invoke(TaxCalculationService $engine, TaxObligations $obligations): Response
    {
        $profile = TaxProfile::current();
        $today = CarbonImmutable::now();
        $year = $engine->forYear($profile, $today->year, $today);
        $next = $obligations->nextPayment($profile, $year, $today);

        // The quarter cards follow the return being paid, else the quarter in progress.
        $focus = $next['quarter'] !== null && $next['year'] === $today->year
            ? $next['quarter']
            : $year['quarters'][$today->quarter];

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
            'checklist' => $obligations->checklist($profile, $today->year, $focus, $transactions, $today),
            'upcoming' => $obligations->upcoming($profile, $year, $today),
            'recent' => $transactions
                ->sortBy([['occurred_on', 'desc'], ['id', 'desc']])
                ->take(5)
                ->values()
                ->map(fn (Transaction $t) => [...TransactionResource::make($t)->resolve(), 'tax_effect' => $effects[$t->id]]),
        ]);
    }
}
