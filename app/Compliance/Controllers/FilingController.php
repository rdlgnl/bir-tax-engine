<?php

namespace App\Compliance\Controllers;

use App\Compliance\Models\Filing;
use App\Compliance\Requests\StoreFilingRequest;
use App\Compliance\Requests\UpdateFilingPaymentRequest;
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

    public function update(UpdateFilingPaymentRequest $request, Filing $filing): RedirectResponse
    {
        $filing->update($request->validated());

        return Inertia::flash('toast', 'Payment recorded. This return is filed and paid.')->back();
    }
}
