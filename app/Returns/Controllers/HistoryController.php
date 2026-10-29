<?php

namespace App\Returns\Controllers;

use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxProfile;
use App\Returns\Requests\ShowHistoryRequest;
use App\Returns\Services\ReturnForm;
use App\Returns\Services\ReturnHistory;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Every return, with the one selected shown in full. A filed return shows
 * its frozen snapshot, and says so if today's records would give a
 * different answer.
 */
class HistoryController extends Controller
{
    public function __invoke(ShowHistoryRequest $request, ReturnHistory $history, ReturnForm $returns): Response
    {
        $profile = TaxProfile::current();
        $today = CarbonImmutable::now();
        $entries = $history->entries($profile, $today);

        $selected = collect($entries)->first(fn (array $e) => $e['year'] === $request->integer('year')
            && $e['quarter'] === $request->integer('quarter')
            && $e['form'] === $request->validated('form'))
            ?? collect($entries)->firstWhere('status', 'filed')
            ?? $entries[0] ?? null;

        $detail = null;

        if ($selected !== null) {
            $filing = $history->filingFor($selected['year'], $selected['quarter'], $selected['form']);
            $live = $returns->layout($returns->snapshot($profile, $selected['year'], $selected['quarter'], $selected['form'], $today));
            $layout = $filing ? $returns->layout($returns->snapshotOf($filing, $profile)) : $live;

            $detail = [
                'entry' => $selected,
                'layout' => $layout,
                'filing' => $filing ? [
                    'filed_on' => $filing->filed_on->toDateString(),
                    'reference_number' => $filing->reference_number,
                    'amount_paid' => $filing->amount_paid !== null ? (float) $filing->amount_paid : null,
                    'paid_on' => $filing->paid_on?->toDateString(),
                    'payment_channel' => $filing->payment_channel,
                    'frozen' => $filing->snapshot !== null,
                ] : null,
                // What the filed return would say if computed from today's records.
                'drift' => $filing && abs($live['payable'] - $layout['payable']) >= 0.01 ? [
                    'payable_now' => $live['payable'],
                    'difference' => round($live['payable'] - $layout['payable'], 2),
                ] : null,
            ];
        }

        return Inertia::render('history/index', [
            'entries' => $entries,
            'detail' => $detail,
        ]);
    }
}
