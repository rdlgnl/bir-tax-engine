<?php

namespace App\Transactions\Controllers;

use App\Compliance\Rules\NotInClosedYear;
use App\Http\Controllers\Controller;
use App\Notices\Services\Notices;
use App\Profiles\Models\TaxProfile;
use App\Returns\Models\Filing;
use App\Returns\Services\ReturnForm;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Enums\TransactionType;
use App\Transactions\Models\Transaction;
use App\Transactions\Requests\ListTransactionsRequest;
use App\Transactions\Requests\SaveTransactionRequest;
use App\Transactions\Resources\TransactionResource;
use App\Transactions\Services\TransactionFilter;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(ListTransactionsRequest $request, TaxCalculationService $engine, TransactionFilter $filter): Response
    {
        $profile = TaxProfile::current();
        $year = $request->integer('year', now()->year);
        $filters = $request->safe()->only(['quarter', 'type', 'search']);

        $all = Transaction::query()->forYear($year)->orderBy('occurred_on')->orderBy('id')->get();
        $effects = $engine->transactionEffects($profile, $all);

        $visible = $filter->apply($all, $filters, CarbonImmutable::now())
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
            // Once the annual return is filed, the year's entries are locked.
            'yearClosed' => NotInClosedYear::closes(CarbonImmutable::create($year)),
        ]);
    }

    public function store(SaveTransactionRequest $request, Notices $notices): RedirectResponse
    {
        // A payment for a recorded invoice settles it: the sale already counts on the invoice's date.
        if (($invoice = $request->paidInvoice()) !== null) {
            $withheld = (float) $request->validated('tax_withheld');
            $invoice->update([
                'invoice_paid' => true,
                'tax_withheld' => $withheld > 0 ? $withheld : $invoice->tax_withheld,
                'certificate_received_on' => $request->validated('certificate_received_on') ?? $invoice->certificate_received_on,
            ]);

            return Inertia::flash('toast', "Invoice {$invoice->invoice_number} marked paid. The sale already counts on its date, so nothing was added.")->back();
        }

        $transaction = new Transaction($request->validated());
        $notices->trackEstimate($transaction->occurred_on->year, $transaction->quarter(), $this->because('After', $transaction), fn () => $transaction->save());

        return Inertia::flash('toast', 'Saved. Your tax is up to date.')->back();
    }

    public function update(SaveTransactionRequest $request, Transaction $transaction, Notices $notices): RedirectResponse
    {
        $transaction->fill($request->validated());
        $notices->trackEstimate($transaction->occurred_on->year, $transaction->quarter(), $this->because('After editing', $transaction), fn () => $transaction->save());

        return Inertia::flash('toast', 'Changes saved.')->back();
    }

    public function destroy(Transaction $transaction, Notices $notices): RedirectResponse
    {
        if (NotInClosedYear::closes($transaction->occurred_on)) {
            return back()->withErrors([
                'transaction' => NotInClosedYear::message($transaction->occurred_on),
            ]);
        }

        $notices->trackEstimate($transaction->occurred_on->year, $transaction->quarter(), $this->because('After deleting', $transaction), fn () => $transaction->delete());

        return Inertia::flash('toast', 'Deleted.')->back();
    }

    /**
     * "After Lumen Digital's payment", for the notice when the estimate moves.
     */
    private function because(string $prefix, Transaction $transaction): string
    {
        $what = match ($transaction->type) {
            TransactionType::Payment => 'payment',
            TransactionType::Invoice => 'invoice '.$transaction->invoice_number,
            TransactionType::Expense => 'expense',
        };

        return "{$prefix} {$transaction->counterparty}’s {$what}";
    }

    /**
     * @return list<int>
     */
    private function filedQuarters(int $year): array
    {
        return array_values(Filing::query()
            ->where('year', $year)
            ->whereIn('form', ReturnForm::INCOME_TAX_FORMS)
            ->pluck('quarter')
            ->map(fn ($quarter) => (int) $quarter)
            ->all());
    }
}
