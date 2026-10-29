<?php

namespace App\Tax\Controllers;

use App\Compliance\Models\Filing;
use App\Http\Controllers\Controller;
use App\Profiles\Enums\IncomeSource;
use App\Profiles\Models\TaxProfile;
use App\Tax\Requests\CompareRequest;
use App\Tax\Services\FilingCalendar;
use App\Tax\Services\TaxCalculationService;
use Inertia\Inertia;
use Inertia\Response;

class CompareController extends Controller
{
    public function __invoke(CompareRequest $request, TaxCalculationService $engine, FilingCalendar $calendar): Response
    {
        $profile = TaxProfile::current();
        $year = now()->year;

        $income = (float) ($request->validated('income') ?? $profile->expected_income ?? 0);
        $expenses = (int) ($request->validated('expenses') ?? $profile->expected_expense_percent ?? 30);
        $salary = $request->validated('salary');

        return Inertia::render('tax/compare', [
            'comparison' => $engine->compare($income, $expenses, $salary !== null ? (float) $salary : null),
            'hasSalaryInProfile' => $profile->income_source === IncomeSource::Both,
            'regime' => $profile->regime?->value,
            'year' => $year,
            'methodLockedOn' => Filing::firstQuarterReturn($year)?->filed_on->toDateString(),
            'nextDecisionDue' => $calendar->incomeTaxDue($year + 1, 1)->toDateString(),
        ]);
    }
}
