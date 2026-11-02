<?php

use App\Compliance\Services\TaxObligations;
use App\Profiles\Models\TaxProfile;
use App\Returns\Services\ReturnForm;
use App\Returns\Services\ReturnHistory;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-20 10:00'));
    $this->profile = TaxProfile::factory()->create();
    // On 8%, purely self-employed: ₱3,300,000 by the end of August.
    Transaction::factory()->on('2026-02-10')->create(['counterparty' => 'Lumen', 'amount' => 1_000_000]);
    Transaction::factory()->on('2026-05-10')->create(['counterparty' => 'Lumen', 'amount' => 1_000_000]);
    Transaction::factory()->on('2026-07-10')->create(['counterparty' => 'Acme', 'amount' => 800_000]);
    Transaction::factory()->on('2026-08-10')->create(['counterparty' => 'Acme', 'amount' => 500_000]);
    Transaction::factory()->expense(validInvoice: true)->on('2026-09-05')->create(['counterparty' => 'Studio rent', 'amount' => 500_000]);
});

test('passing ₱3,000,000 on 8% moves the year to graduated rates from that quarter, crediting the 8% paid', function () {
    $year = app(TaxCalculationService::class)->forYear($this->profile, 2026);

    expect($year['switched'])->toBe(['month' => 8, 'quarter' => 3, 'vat_from' => '2026-09-01', 'register_by' => '2026-09-30'])
        ->and($year['quarters'][1])->toMatchArray(['regime' => 'eight_percent', 'income_tax' => 60000.0, 'due' => 60000.0, 'percentage_tax' => null])
        ->and($year['quarters'][2])->toMatchArray(['regime' => 'eight_percent', 'income_tax' => 140000.0, 'due' => 80000.0, 'percentage_tax' => null])
        // ₱3,300,000 less ₱500,000 of expenses with invoices: ₱402,500 + 30% of ₱800,000.
        ->and($year['quarters'][3])->toMatchArray([
            'regime' => 'graduated', 'deduction_method' => 'itemized', 'deduction' => 500000.0, 'taxable' => 2800000.0,
            'income_tax' => 642500.0, 'prior_payments' => 140000.0, 'due' => 502500.0,
            // 3% on January to August, on the Q3 2551Q.
            'percentage_tax' => 99000.0,
        ])
        // VAT from September replaces percentage tax; the annual return is the 1701.
        ->and($year['quarters'][4])->toMatchArray(['regime' => 'graduated', 'percentage_tax' => null, 'form' => '1701']);
});

test('the switch quarter files a graduated 1701Q and a 2551Q, and the calendar asks for VAT registration', function () {
    $snapshot = app(ReturnForm::class)->snapshot($this->profile, 2026, 3, '1701Q');
    $result = app(TaxCalculationService::class)->forYear($this->profile, 2026);

    expect($snapshot)->toMatchArray(['regime' => 'graduated', 'deduction_method' => 'itemized'])
        ->and(app(ReturnHistory::class)->formsForQuarter($this->profile, $result, 2))->toBe(['1701Q'])
        ->and(app(ReturnHistory::class)->formsForQuarter($this->profile, $result, 3))->toBe(['1701Q', '2551Q'])
        ->and(app(ReturnHistory::class)->formsForQuarter($this->profile, $result, 4))->toBe(['1701']);

    $this->get('/file/2026/3/2551Q')->assertOk();
    $this->get('/file/2026/2/2551Q')->assertNotFound();

    $deadlines = collect(app(TaxObligations::class)->deadlines($this->profile, $result));
    expect($deadlines->firstWhere('title', 'Register for VAT · sales passed ₱3,000,000'))->toMatchArray(['date' => '2026-09-30', 'kind' => 'reminder', 'href' => '/profile/registration'])
        ->and($deadlines->firstWhere('title', 'Q3 2551Q · percentage tax'))->toMatchArray(['amount' => 99000.0]);
});

test('My tax explains the switch', function () {
    $this->get('/tax/3')->assertInertia(fn (Assert $page) => $page
        ->component('tax/show')
        ->where('result.switched.quarter', 3)
        ->where('regimeLabel', 'Graduated rates')
        ->where('explanation.reasons.2.text', 'Sales passed ₱3M in August, so graduated rates apply from Q3'));

    $this->get('/tax/2')->assertInertia(fn (Assert $page) => $page->where('regimeLabel', 'Flat 8%'));
});
