<?php

namespace App\Transactions\Controllers;

use App\Http\Controllers\Controller;
use App\Transactions\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Records that a client's Form 2307 arrived (or un-records it).
 */
class TransactionCertificateController extends Controller
{
    public function __invoke(Request $request, Transaction $transaction): RedirectResponse
    {
        $request->validate(['received' => ['required', 'boolean']]);

        if ((float) $transaction->tax_withheld <= 0) {
            throw ValidationException::withMessages([
                'received' => 'Nothing was withheld on this payment, so there is no 2307 to track.',
            ]);
        }

        $transaction->update([
            'certificate_received_on' => $request->boolean('received') ? now()->toDateString() : null,
        ]);

        return Inertia::flash('toast', $request->boolean('received')
            ? "2307 from {$transaction->counterparty} marked received. The credit is confirmed."
            : '2307 marked as not received.')->back();
    }
}
