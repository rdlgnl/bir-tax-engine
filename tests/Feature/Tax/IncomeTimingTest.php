<?php

use App\Profiles\Models\TaxProfile;
use App\Profiles\Models\TaxYear;
use App\Tax\Enums\BooksMethod;
use App\Tax\Services\TaxCalculationService;
use App\Tax\Services\VatCalculator;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-05 10:00'));
});

function quarters(TaxProfile $profile, int $year): array
{
    return app(TaxCalculationService::class)->forYear($profile, $year)['quarters'];
}

test('on cash books a sale counts for income tax when paid, with the tax withheld from it', function () {
    $profile = TaxProfile::factory()->graduated()->create(['books_method' => BooksMethod::Cash]);
    $invoice = Transaction::factory()->invoice()->on('2026-03-10')->create(['amount' => 100_000, 'tax_withheld' => 5_000]);

    expect(quarters($profile, 2026)[1])->toMatchArray(['quarter_gross' => 0.0, 'withheld' => 0.0])
        // Percentage tax counts it on its invoice date all the same.
        ->and(quarters($profile, 2026)[1])->toMatchArray(['percentage_tax_base' => 100000.0, 'percentage_tax' => 3000.0]);

    $invoice->update(['invoice_paid' => true, 'invoice_paid_on' => '2026-05-05']);

    expect(quarters($profile, 2026)[2])->toMatchArray(['quarter_gross' => 100000.0, 'quarter_withheld' => 5000.0, 'percentage_tax' => 0.0])
        ->and(quarters($profile, 2026)[1]['quarter_gross'])->toBe(0.0);
});

test('on accrual books a sale counts on its invoice date, paid or not', function () {
    $profile = TaxProfile::factory()->graduated()->create();
    Transaction::factory()->invoice()->on('2026-03-10')->create(['amount' => 100_000]);

    expect(quarters($profile, 2026)[1])->toMatchArray(['quarter_gross' => 100000.0, 'percentage_tax' => 3000.0]);
});

test('an invoice from December paid in January counts in January on cash books', function () {
    $profile = TaxProfile::factory()->graduated()->create(['books_method' => BooksMethod::Cash]);
    Transaction::factory()->invoice(paid: true)->on('2025-12-15')->create(['amount' => 50_000, 'invoice_paid_on' => '2026-01-08']);

    expect(app(TaxCalculationService::class)->forYear($profile, 2025)['gross'])->toBe(0.0)
        ->and(quarters($profile, 2026)[1]['quarter_gross'])->toBe(50000.0);
});

test('before April 27, 2024 percentage tax counted a service when paid (RR 3-2024)', function () {
    $profile = TaxProfile::factory()->graduated()->create();
    // Invoiced in March 2024, paid in May; and one invoiced in May, still unpaid.
    Transaction::factory()->invoice(paid: true)->on('2024-03-20')->create(['amount' => 100_000, 'invoice_paid_on' => '2024-05-10']);
    Transaction::factory()->invoice()->on('2024-05-02')->create(['amount' => 40_000]);

    expect(quarters($profile, 2024)[1]['percentage_tax_base'])->toBe(0.0)
        ->and(quarters($profile, 2024)[2])->toMatchArray(['percentage_tax_base' => 140000.0, 'percentage_tax' => 4200.0])
        // Income tax stays on the books' method: both on their invoice dates.
        ->and(quarters($profile, 2024)[1]['quarter_gross'])->toBe(100000.0);
});

test('VAT on a service invoiced before April 27, 2024 is declared when paid (Sec. 7(a))', function () {
    TaxProfile::factory()->create(['vat_registered' => true, 'regime' => 'graduated', 'deduction_method' => 'osd']);
    Transaction::factory()->invoice(paid: true)->on('2024-03-20')->create(['amount' => 100_000, 'vat_amount' => 12_000, 'invoice_paid_on' => '2024-05-10']);
    Transaction::factory()->invoice()->on('2024-05-02')->create(['amount' => 50_000, 'vat_amount' => 6_000]);

    $vat = app(VatCalculator::class)->forYear(2024);

    expect($vat[1]['output_vat'])->toBe(0.0)
        ->and($vat[2]['output_vat'])->toBe(18000.0);
});

test('the books method is chosen on the books page, applies from this year, and a payment records when it settled an invoice', function () {
    $this->seed(DemoTaxpayerSeeder::class);
    $this->travelTo(CarbonImmutable::parse('2027-02-01 10:00'));

    $this->put('/books/type', ['books_method' => 'cash'])->assertRedirect();

    expect(TaxProfile::current()->books_method)->toBe(BooksMethod::Cash)
        ->and(TaxYear::query()->where('year', 2026)->sole()->books_method)->toBe(BooksMethod::Accrual)
        ->and(TaxProfile::current()->forYear(2026)->books_method)->toBe(BooksMethod::Accrual);

    $this->post('/transactions', ['type' => 'payment', 'occurred_on' => '2027-01-29', 'counterparty' => 'Studio Uno', 'amount' => 40_000, 'tax_withheld' => 0, 'invoice_number' => '0142'])
        ->assertSessionHasNoErrors();

    expect(Transaction::query()->where('invoice_number', '0142')->where('type', 'invoice')->sole()->invoice_paid_on->toDateString())->toBe('2027-01-29');
});
