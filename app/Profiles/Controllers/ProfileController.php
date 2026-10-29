<?php

namespace App\Profiles\Controllers;

use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxProfile;
use App\Profiles\Resources\TaxProfileResource;
use App\Returns\Models\Filing;
use App\Tax\Services\FilingCalendar;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __invoke(FilingCalendar $calendar): Response
    {
        $year = now()->year;

        return Inertia::render('profile/show', [
            'profile' => TaxProfileResource::make(TaxProfile::current()),
            'year' => $year,
            'methodLockedOn' => Filing::firstQuarterReturn($year)?->filed_on->toDateString(),
            'nextYearDecisionDue' => $calendar->incomeTaxDue($year + 1, 1)->toDateString(),
            'rulePack' => ['version' => config('tax.version'), 'reviewed' => config('tax.reviewed')],
        ]);
    }
}
