<?php

namespace App\Compliance\Controllers;

use App\Compliance\Models\Filing;
use App\Compliance\Requests\StoreFilingRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class FilingController extends Controller
{
    public function store(StoreFilingRequest $request): RedirectResponse
    {
        $filing = Filing::create($request->safe()->except('paid'));

        $what = $filing->quarter === 4 ? "Your {$filing->year} annual return" : "Q{$filing->quarter} {$filing->year}";

        return Inertia::flash('toast', "{$what} is marked filed".($filing->isPaid() ? ' and paid' : '').'. Later changes become an amendment.')
            ->back();
    }
}
