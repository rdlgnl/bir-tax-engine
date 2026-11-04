<?php

namespace App\Profiles\Controllers;

use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxProfile;
use App\Profiles\Requests\UpdateEfpsRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * How the taxpayer e-files: through eBIRForms, or eFPS under the group the
 * BIR assigned, from a month on. It moves when the 0619-E and 1601-C are due.
 */
class EfpsController extends Controller
{
    public function update(UpdateEfpsRequest $request): RedirectResponse
    {
        $group = $request->validated('efps_group');
        $from = $request->validated('efps_from');

        TaxProfile::current()->update([
            'efps_group' => $group,
            'efps_from' => $group !== null && $from !== null ? "{$from}-01" : null,
        ]);

        return Inertia::flash('toast', 'How you file is saved.')->back();
    }
}
