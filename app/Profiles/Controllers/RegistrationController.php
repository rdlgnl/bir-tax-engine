<?php

namespace App\Profiles\Controllers;

use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxProfile;
use App\Profiles\Services\RegistrationUpdates;
use App\Returns\Forms\Items\Form1905Items;
use App\Returns\Forms\OfficialFormFiller;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Keeping the BIR registration in step with the profile: what to update,
 * and Form 1905 filled in for the update the app can prepare.
 */
class RegistrationController extends Controller
{
    public function show(RegistrationUpdates $updates): Response
    {
        $profile = TaxProfile::current();
        $today = CarbonImmutable::now();

        return Inertia::render('profile/registration', [
            'year' => $today->year,
            'corOutdated' => $profile->corOutdated(),
            'updates' => $updates->for($profile, $today),
            'edition' => config('tax.forms.1905.edition'),
        ]);
    }

    /**
     * The 1905 that takes percentage tax off the COR. Only an 8% taxpayer
     * still registered for percentage tax needs it.
     */
    public function form(OfficialFormFiller $filler, Form1905Items $items): HttpResponse
    {
        $profile = TaxProfile::current();
        $year = now()->year;

        abort_unless($profile->corOutdated(), 404);

        return response($filler->render('1905', $items->removePercentageTax($profile, $year)), headers: [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=1905-{$year}-remove-percentage-tax.pdf",
        ]);
    }
}
