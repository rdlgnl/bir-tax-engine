<?php

namespace App\Loans\Controllers;

use App\Http\Controllers\Controller;
use App\Loans\Models\Loan;
use App\Loans\Requests\MarkLoanFiledRequest;
use App\Loans\Requests\SaveLoanRequest;
use App\Loans\Services\LoanTax;
use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\Items\Form2000Items;
use App\Returns\Forms\OfficialFormFiller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Loans the taxpayer signed (design 9-03) and the Form 2000 for their
 * documentary stamp tax (9-04). A loan isn't income, so it stays out of
 * the transactions and the income tax.
 */
class LoanController extends Controller
{
    public function __construct(private LoanTax $tax) {}

    public function index(): Response
    {
        return Inertia::render('loans/index', [
            'loans' => Loan::query()->latest('signed_on')->get()->map(fn (Loan $loan) => $this->resource($loan))->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('loans/edit', ['loan' => null]);
    }

    public function store(SaveLoanRequest $request): RedirectResponse
    {
        $loan = Loan::query()->create($request->validated());

        Inertia::flash('toast', 'Loan saved.');

        return redirect()->route('loans.show', $loan);
    }

    public function show(Loan $loan): Response
    {
        return Inertia::render('loans/edit', ['loan' => $this->resource($loan)]);
    }

    public function update(SaveLoanRequest $request, Loan $loan): RedirectResponse
    {
        $loan->update($request->validated());

        return Inertia::flash('toast', 'Loan saved.')->back();
    }

    public function destroy(Loan $loan): RedirectResponse
    {
        $loan->delete();
        Inertia::flash('toast', 'Loan deleted.');

        return redirect()->route('loans.index');
    }

    public function form(Loan $loan): Response
    {
        return Inertia::render('loans/form', [
            'loan' => $this->resource($loan),
            'edition' => (string) config('tax.forms.2000.edition'),
            'atc' => (string) config('tax.dst.loan.atc'),
            'rate' => sprintf('%s per %d', number_format((float) config('tax.dst.loan.rate'), 2), (int) config('tax.dst.loan.per')),
        ]);
    }

    public function pdf(Request $request, Loan $loan, Form2000Items $items, OfficialFormFiller $filler): HttpResponse
    {
        $dst = $this->tax->for($loan);
        $values = $items->values(TaxProfile::current(), $loan, $dst['tax'], $dst['penalties']);

        return response($filler->render('2000', $values), headers: [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('print') ? 'inline' : 'attachment')."; filename=2000-{$loan->signed_on->format('Y-m')}.pdf",
        ]);
    }

    public function filed(MarkLoanFiledRequest $request, Loan $loan): RedirectResponse
    {
        abort_if($loan->filedByLender(), 404);
        $loan->forceFill([
            'filed_on' => $request->validated('filed_on'),
            'reference_number' => $request->validated('reference_number'),
            'amount_paid' => $request->validated('amount_paid'),
        ])->save();

        return Inertia::flash('toast', 'Form 2000 is marked filed.')->back();
    }

    /**
     * @return array<string, mixed>
     */
    private function resource(Loan $loan): array
    {
        return [
            'id' => $loan->id,
            'signed_on' => $loan->signed_on->toDateString(),
            'amount' => (float) $loan->amount,
            'lender' => $loan->lender,
            'lender_tin' => $loan->lender_tin,
            'lender_kind' => $loan->lender_kind,
            'term_months' => $loan->term_months,
            'filed_on' => $loan->filed_on?->toDateString(),
            'reference_number' => $loan->reference_number,
            'amount_paid' => $loan->amount_paid !== null ? (float) $loan->amount_paid : null,
            'dst' => $this->tax->for($loan),
        ];
    }
}
