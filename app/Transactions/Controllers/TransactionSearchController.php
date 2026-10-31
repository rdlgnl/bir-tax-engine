<?php

namespace App\Transactions\Controllers;

use App\Http\Controllers\Controller;
use App\Returns\Services\RecordsExport;
use App\Transactions\Models\Transaction;
use App\Transactions\Requests\SearchTransactionsRequest;
use App\Transactions\Resources\TransactionResource;
use App\Transactions\Services\TransactionFilter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * "Find a record": every record, searched and filtered, and exported
 * exactly as found.
 */
class TransactionSearchController extends Controller
{
    public function __construct(private TransactionFilter $filter) {}

    public function show(SearchTransactionsRequest $request): Response
    {
        $today = CarbonImmutable::now();
        [$from, $to] = $request->range($today);
        $inPeriod = $this->filter->inPeriod($from, $to);
        $base = $this->filter->apply($inPeriod, $request->safe()->only(['search', 'type']), $today);
        $results = $this->results($request, $today);
        $first = Transaction::query()->min('occurred_on');

        return Inertia::render('transactions/search', [
            'filters' => [
                'search' => $request->validated('search') ?? '',
                'type' => $request->validated('type'),
                'period' => $request->validated('period') ?? 'year',
                'year' => $request->integer('year', $today->year),
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'flag' => $request->validated('flag'),
            ],
            'results' => $results
                ->sortBy([['occurred_on', 'desc'], ['id', 'desc']])
                ->values()
                ->map(fn (Transaction $t) => TransactionResource::make($t)->resolve()),
            'summary' => [
                'count' => $results->count(),
                'billed' => round((float) $results->filter(fn (Transaction $t) => $t->isIncome())->sum('amount'), 2),
                'spent' => round((float) $results->filter(fn (Transaction $t) => ! $t->isIncome())->sum('amount'), 2),
            ],
            // Counted within the search and type, so each says what clicking it would leave.
            'flagCounts' => $this->filter->flagCounts($base, $today),
            'years' => range($today->year, $first ? CarbonImmutable::parse($first)->year : $today->year),
        ]);
    }

    public function export(SearchTransactionsRequest $request, RecordsExport $export): HttpResponse
    {
        $today = CarbonImmutable::now();
        [$from, $to] = $request->range($today);
        $name = "records-{$from->toDateString()}-to-{$to->toDateString()}.csv";

        return response($export->transactionsCsv($this->results($request, $today)), headers: [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$name}\"",
        ]);
    }

    /**
     * @return Collection<int, Transaction>
     */
    private function results(SearchTransactionsRequest $request, CarbonImmutable $today): Collection
    {
        [$from, $to] = $request->range($today);

        return $this->filter->apply(
            $this->filter->inPeriod($from, $to),
            $request->safe()->only(['search', 'type', 'flag']),
            $today,
        );
    }
}
