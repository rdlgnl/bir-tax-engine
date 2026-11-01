<?php

use App\Compliance\Services\TaxObligations;
use App\Data\Services\LocalData;
use App\Loans\Models\Loan;
use App\Loans\Services\LoanTax;
use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\Items\Form2000Items;
use App\Tax\Services\DocumentaryStampTax;
use App\Tax\Services\TaxCalculationService;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 10:00'));
    TaxProfile::factory()->create();
});

/** Design 9-03: ₱500,000 from Ramon Cruz, signed Oct 20 for a year. */
function ramonsLoan(array $overrides = []): array
{
    return [
        'signed_on' => '2026-10-20', 'amount' => '500000', 'lender' => 'Ramon Cruz', 'lender_tin' => '234-567-890-000',
        'lender_kind' => 'person', 'term_months' => '12', ...$overrides,
    ];
}

function loanDeadlines(): array
{
    $profile = TaxProfile::current();
    $year = app(TaxCalculationService::class)->forYear($profile, 2026);

    return array_values(array_filter(app(TaxObligations::class)->deadlines($profile, $year), fn (array $d) => $d['kind'] === 'dst'));
}

test('DST on a loan is ₱1.50 for each ₱200 or fraction, a share of it under a year', function () {
    $dst = app(DocumentaryStampTax::class);

    expect($dst->onLoan(500_000, 12))->toBe(3750.0)
        ->and($dst->onLoan(500_100, 24))->toBe(3751.5)
        ->and($dst->onLoan(500_000, 6))->toBe(1875.0);
});

test('a loan from a person is due on Form 2000 5 days after its month', function () {
    $this->post('/loans', ramonsLoan())->assertSessionHasNoErrors();
    $loan = Loan::query()->sole();

    $this->get("/loans/{$loan->id}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('loans/edit')
        ->where('loan.dst', ['tax' => 3750, 'due_date' => '2026-11-05', 'status' => 'due', 'penalties' => null, 'total' => 3750]));

    expect(loanDeadlines())->toHaveCount(1)
        ->and(loanDeadlines()[0])->toMatchArray([
            'date' => '2026-11-05', 'title' => 'Form 2000 · DST on the loan from Ramon Cruz', 'status' => 'due', 'amount' => 3750.0,
            'href' => "/loans/{$loan->id}",
        ]);
});

test('after its due date the DST adds a surcharge and interest', function () {
    $this->post('/loans', ramonsLoan());
    $this->travelTo(CarbonImmutable::parse('2026-11-12 10:00'));

    $dst = app(LoanTax::class)->for(Loan::query()->sole());

    expect($dst)->toMatchArray(['status' => 'overdue', 'total' => 4129.32])
        ->and($dst['penalties'])->toMatchArray(['surcharge' => 375.0, 'interest' => 4.32, 'days' => 7]);
});

test('Form 2000 is filled with the lender and the loan on Schedule 1', function () {
    $this->post('/loans', ramonsLoan());
    $loan = Loan::query()->sole();

    expect(app(Form2000Items::class)->values(TaxProfile::current(), $loan, 3750))->toMatchArray([
        '1' => '102026', '2.no' => true, 'tin.1' => '123', 'tin.branch' => '00000', '5' => '028',
        '10.creditor' => true, '11' => 'RAMON CRUZ', '12' => '23456789000000',
        '14' => 3750.0, '16' => 3750.0, '17D' => null, '18' => 3750.0,
        's1.1.atc' => 'DS106', 's1.1.base' => 500000.0, 's1.1.rate' => 'P1.50 / P200', 's1.1.due' => 3750.0, 's1.total' => 3750.0,
    ]);

    $response = $this->get("/loans/{$loan->id}/pdf")->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
    $this->get("/loans/{$loan->id}/form")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('loans/form')->where('atc', 'DS106'));
});

test('marking Form 2000 filed clears the deadline', function () {
    $this->post('/loans', ramonsLoan());
    $loan = Loan::query()->sole();

    $this->post("/loans/{$loan->id}/filed", ['filed_on' => '2026-10-27', 'reference_number' => 'EBIR-2000', 'amount_paid' => '3750'])
        ->assertSessionHasNoErrors();

    expect($loan->fresh())->filed_on->toDateString()->toBe('2026-10-27')->amount_paid->toBe('3750.00')
        ->and(app(LoanTax::class)->for($loan->fresh())['status'])->toBe('filed')
        ->and(loanDeadlines())->toBe([]);
});

test('a bank files the DST itself, so there is nothing to file', function () {
    $this->post('/loans', ramonsLoan(['lender' => 'BDO', 'lender_kind' => 'bank']));
    $loan = Loan::query()->sole();

    expect(app(LoanTax::class)->for($loan)['status'])->toBe('bank')
        ->and(loanDeadlines())->toBe([]);
    $this->post("/loans/{$loan->id}/filed", ['filed_on' => '2026-10-27'])->assertNotFound();
});

test('a loan is checked, edited and deleted, and kept in backups', function () {
    $this->post('/loans', ramonsLoan(['lender_kind' => 'friend']))->assertSessionHasErrors('lender_kind');
    $this->post('/loans', ramonsLoan(['signed_on' => '2026-12-01']))->assertSessionHasErrors('signed_on');
    $this->post('/loans', ramonsLoan(['amount' => '0']))->assertSessionHasErrors('amount');

    $this->post('/loans', ramonsLoan());
    $loan = Loan::query()->sole();
    $this->put("/loans/{$loan->id}", ramonsLoan(['amount' => '600000']))->assertSessionHasNoErrors();
    expect($loan->fresh()->amount)->toBe('600000.00')
        ->and(app(LocalData::class)->rows()['loans'])->toHaveCount(1);

    $this->get('/loans')->assertOk()->assertInertia(fn (Assert $page) => $page->has('loans', 1)->where('loans.0.dst.tax', 4500));

    $this->delete("/loans/{$loan->id}")->assertRedirect('/loans');
    expect(Loan::query()->count())->toBe(0);
});
