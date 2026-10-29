<?php

namespace App\Transactions\Controllers;

use App\Compliance\Rules\NotInFiledQuarter;
use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxProfile;
use App\Returns\Models\Filing;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Enums\CertificateStatus;
use App\Transactions\Models\Transaction;
use App\Transactions\Requests\ListTransactionsRequest;
use App\Transactions\Requests\SaveTransactionRequest;
use App\Transactions\Resources\TransactionResource;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(ListTransactionsRequest $request, TaxCalculationService $engine): Response
    {
        $profile = TaxProfile::current();
        $year = $request->integer('year', now()->year);
        $filters = $request->safe()->only(['quarter', 'type', 'search']);

        $all = Transaction::query()->forYear($year)->orderBy('occurred_on')->orderBy('id')->get();
        $effects = $engine->transactionEffects($profile, $all);

        $visible = $all
            ->filter(fn (Transaction $t) => $this->matches($t, $filters))
            ->sortBy([['occurred_on', 'desc'], ['id', 'desc']])
            ->values()
            ->map(fn (Transaction $t) => [...TransactionResource::make($t)->resolve(), 'tax_effect' => $effects[$t->id]]);

        $firstDate = Transaction::query()->min('occurred_on');

        return Inertia::render('transactions/index', [
            'transactions' => $visible,
            'filters' => [
                'year' => $year,
                'quarter' => $filters['quarter'] ?? null,
                'type' => $filters['type'] ?? null,
                'search' => $filters['search'] ?? '',
            ],
            'years' => range(now()->year, $firstDate ? CarbonImmutable::parse($firstDate)->year : now()->year),
            'yearGross' => (float) $all->filter(fn (Transaction $t) => $t->isIncome())->sum('amount'),
            'filedQuarters' => $this->filedQuarters($year),
        ]);
    }

    public function store(SaveTransactionRequest $request): RedirectResponse
    {
        Transaction::create($request->validated());

        return Inertia::flash('toast', 'Saved. Your tax is up to date.')->back();
    }

    public function update(SaveTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $transaction->update($request->validated());

        return Inertia::flash('toast', 'Changes saved.')->back();
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        if (Filing::incomeTaxReturnCovering($transaction->occurred_on) !== null) {
            return back()->withErrors([
                'transaction' => NotInFiledQuarter::message($transaction->occurred_on),
            ]);
        }

        $transaction->delete();

        return Inertia::flash('toast', 'Deleted.')->back();
    }

    /**
     * @param  array{quarter?: int|string|null, type?: string|null, search?: string|null}  $filters
     */
    private function matches(Transaction $transaction, array $filters): bool
    {
        $quarter = $filters['quarter'] ?? null;
        $type = $filters['type'] ?? null;
        $search = trim((string) ($filters['search'] ?? ''));

        if ($quarter !== null && $transaction->quarter() !== (int) $quarter) {
            return false;
        }

        if ($type === 'withheld' && $transaction->certificateStatus(now()) === CertificateStatus::NotNeeded) {
            return false;
        }

        if ($type !== null && $type !== 'withheld' && $transaction->type->value !== $type) {
            return false;
        }

        if ($search === '') {
            return true;
        }

        $number = (float) preg_replace('/[^\d.]/', '', $search);

        return Str::contains($transaction->counterparty, $search, ignoreCase: true)
            || Str::contains((string) $transaction->invoice_number, $search, ignoreCase: true)
            || Str::contains((string) $transaction->description, $search, ignoreCase: true)
            || ($number > 0 && (float) $transaction->amount === $number);
    }

    /**
     * @return list<int>
     */
    private function filedQuarters(int $year): array
    {
        return array_values(Filing::query()
            ->where('year', $year)
            ->whereIn('form', ['1701Q', '1701A', '1701'])
            ->pluck('quarter')
            ->map(fn ($quarter) => (int) $quarter)
            ->all());
    }
}
