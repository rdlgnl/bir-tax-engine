<?php

namespace App\Tax\Controllers;

use App\Compliance\Models\Filing;
use App\Compliance\Services\TaxObligations;
use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxProfile;
use App\Tax\Services\TaxCalculationService;
use App\Tax\Services\TaxExplainer;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

class TaxController extends Controller
{
    public function show(
        TaxCalculationService $engine,
        TaxExplainer $explainer,
        TaxObligations $obligations,
        ?int $quarter = null,
    ): Response {
        $profile = TaxProfile::current();
        $today = CarbonImmutable::now();
        $year = $today->year;
        $result = $engine->forYear($profile, $year, $today);

        // Open on the return that needs attention, else the latest finished quarter.
        $quarter ??= $obligations->nextPayment($profile, $result, $today)['quarter']['quarter']
            ?? max(1, $today->quarter - 1);

        $transactions = Transaction::query()->forYear($year)->get();

        return Inertia::render('tax/show', [
            'year' => $year,
            'quarter' => $quarter,
            'result' => $result,
            'explanation' => $explainer->explain($profile, $year, $result['quarters'][$quarter], $transactions, $today),
            'methodLockedOn' => Filing::firstQuarterReturn($year)?->filed_on->toDateString(),
            'regimeLabel' => $profile->regime?->label(),
            'purelySelfEmployed' => $profile->isPurelySelfEmployed(),
            'rulePack' => ['version' => config('tax.version'), 'reviewed' => config('tax.reviewed')],
        ]);
    }
}
