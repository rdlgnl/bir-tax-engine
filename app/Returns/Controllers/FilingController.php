<?php

namespace App\Returns\Controllers;

use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxProfile;
use App\Returns\Models\Filing;
use App\Returns\Requests\StoreFilingRequest;
use App\Returns\Requests\UpdateFilingPaymentRequest;
use App\Returns\Services\ReturnForm;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class FilingController extends Controller
{
    public function store(StoreFilingRequest $request, ReturnForm $returns): RedirectResponse
    {
        $data = $request->safe()->except('paid');

        // Freeze the figures as filed, so history never shifts under them.
        $filing = Filing::create([
            ...$data,
            'snapshot' => $returns->snapshot(TaxProfile::current(), (int) $data['year'], (int) $data['quarter'], $data['form']),
        ]);

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
