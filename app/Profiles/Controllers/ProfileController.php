<?php

namespace App\Profiles\Controllers;

use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxProfile;
use App\Profiles\Models\TaxYear;
use App\Profiles\Resources\TaxProfileResource;
use App\Returns\Models\Filing;
use App\Tax\Enums\TaxpayerClassification;
use App\Tax\Services\FilingCalendar;
use App\Tax\Services\TaxCalculationService;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __invoke(FilingCalendar $calendar, TaxCalculationService $engine): Response
    {
        $year = now()->year;
        $profile = TaxProfile::current();
        // The BIR classes a taxpayer by the year before's sales; a first year by this year's.
        $lastYear = $engine->forYear($profile, $year - 1)['gross'];
        $estimate = TaxpayerClassification::fromGross($lastYear > 0 ? $lastYear : $engine->forYear($profile, $year)['gross']);
        $assigned = TaxYear::query()->where('year', $year)->first()?->classification;

        return Inertia::render('profile/show', [
            'profile' => TaxProfileResource::make($profile),
            'classification' => [
                'year' => $year,
                'assigned' => $assigned?->value,
                'estimate' => ['value' => $estimate->value, 'label' => $estimate->label()],
                'options' => array_map(fn (TaxpayerClassification $c) => ['value' => $c->value, 'label' => $c->label()], TaxpayerClassification::cases()),
            ],
            'year' => $year,
            'methodLockedOn' => Filing::firstQuarterReturn($year)?->filed_on->toDateString(),
            'nextYearDecisionDue' => $calendar->incomeTaxDue($year + 1, 1)->toDateString(),
            'rulePack' => ['version' => config('tax.version'), 'reviewed' => config('tax.reviewed')],
        ]);
    }
}
