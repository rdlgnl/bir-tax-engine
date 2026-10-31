<?php

namespace App\Compliance\Controllers;

use App\Compliance\Services\TaxObligations;
use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxProfile;
use App\Returns\Models\Filing;
use App\Returns\Services\ReturnForm;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

class ComplianceController extends Controller
{
    public function __invoke(TaxCalculationService $engine, TaxObligations $obligations, ?int $quarter = null): Response
    {
        $profile = TaxProfile::current();
        $today = CarbonImmutable::now();
        $year = $today->year;
        $result = $engine->forYear($profile, $year, $today);
        $next = $obligations->nextPayment($profile, $result, $today);

        // Open on the return that needs attention, else the quarter in progress.
        $quarter ??= $next['year'] === $year && $next['quarter'] !== null
            ? $next['quarter']['quarter']
            : $today->quarter;

        $transactions = Transaction::query()->forYear($year)->orderBy('occurred_on')->get();
        $filedQuarters = array_values(Filing::query()
            ->where('year', $year)
            ->whereIn('form', ReturnForm::INCOME_TAX_FORMS)
            ->pluck('quarter')
            ->all());

        return Inertia::render('compliance/show', [
            'year' => $year,
            'quarter' => $quarter,
            'quarters' => array_values($result['quarters']),
            'checklist' => $obligations->checklist($profile, $year, $result['quarters'][$quarter], $transactions, $today),
            'certificates' => $obligations->certificates($transactions, $filedQuarters, $today),
            'corOutdated' => $profile->corOutdated(),
        ]);
    }
}
