<?php

namespace App\Profiles\Controllers;

use App\Http\Controllers\Controller;
use App\Profiles\Enums\IncomeSource;
use App\Profiles\Models\TaxProfile;
use App\Profiles\Requests\UpdateJobRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * The job of someone with a side business: who employs them and their
 * monthly taxable salary, used for the annual return.
 */
class JobController extends Controller
{
    public function update(UpdateJobRequest $request): RedirectResponse
    {
        $profile = TaxProfile::current();
        abort_unless($profile?->income_source === IncomeSource::Both, 404);

        $profile->update($request->validated());

        return Inertia::flash('toast', 'Your job is updated.')->back();
    }
}
