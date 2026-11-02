<?php

namespace App\Profiles\Controllers;

use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxYear;
use App\Profiles\Requests\UpdateClassificationRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * The size class the BIR assigned for a year (micro, small, medium or
 * large), as ORUS shows it. Blank goes back to estimating from sales.
 */
class ClassificationController extends Controller
{
    public function update(UpdateClassificationRequest $request): RedirectResponse
    {
        TaxYear::query()->updateOrCreate(
            ['year' => $request->integer('year')],
            ['classification' => $request->validated('classification')],
        );

        return Inertia::flash('toast', 'Your taxpayer classification is saved.')->back();
    }
}
