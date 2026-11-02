<?php

use App\Profiles\Models\TaxProfile;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Enums\TransactionType;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    Storage::fake('local');
    $this->seed(DemoTaxpayerSeeder::class);
});

/** The seeded Oct 26 invoice 0142 to Studio Uno, ₱40,000, unpaid. */
function invoice0142(): Transaction
{
    return Transaction::query()->where('type', TransactionType::Invoice)->where('invoice_number', '0142')->sole();
}

function yearGross(): float
{
    return app(TaxCalculationService::class)->forYear(TaxProfile::current(), 2026)['gross'];
}

function invoicePayment(array $overrides = []): array
{
    return ['type' => 'payment', 'occurred_on' => '2026-10-27', 'counterparty' => 'Studio Uno', 'amount' => 40_000, 'tax_withheld' => 0, 'invoice_number' => '0142', ...$overrides];
}

test('a payment for a recorded invoice marks it paid instead of counting the sale again', function () {
    // On 8% the invoice counts once, when it's paid (RR 8-2018 Sec. 2(g)).
    $gross = yearGross();
    $count = Transaction::query()->count();

    $this->post('/transactions', invoicePayment(['tax_withheld' => 2_000]))->assertRedirect()->assertSessionHasNoErrors();

    expect(invoice0142())->invoice_paid->toBeTrue()->tax_withheld->toBe('2000.00')
        ->and(Transaction::query()->count())->toBe($count)
        ->and(yearGross())->toBe($gross + 40_000);
});

test('a payment that doesn’t match the invoice, or for one already paid, is turned back', function () {
    $this->post('/transactions', invoicePayment(['amount' => 35_000]))
        ->assertSessionHasErrors(['invoice_number' => 'Invoice 0142 is recorded for ₱40,000.00. Mark that invoice paid (or edit it) instead, so the sale counts once.']);

    invoice0142()->update(['invoice_paid' => true]);

    $this->post('/transactions', invoicePayment())
        ->assertSessionHasErrors(['invoice_number' => 'Invoice 0142 is already recorded as paid, so this payment would count the sale twice.']);
});

test('a payment quoting an invoice that isn’t recorded is just a payment', function () {
    $count = Transaction::query()->count();

    $this->post('/transactions', invoicePayment(['invoice_number' => '0999']))->assertSessionHasNoErrors();

    expect(Transaction::query()->count())->toBe($count + 1);
});

test('an invoice number is used once', function () {
    $this->post('/transactions', ['type' => 'invoice', 'occurred_on' => '2026-10-27', 'counterparty' => 'Kape Collective', 'amount' => 10_000, 'tax_withheld' => 0, 'invoice_number' => '0142'])
        ->assertSessionHasErrors(['invoice_number' => 'An invoice with this number is already recorded.']);

    // Editing the invoice itself keeps its number.
    $this->put('/transactions/'.invoice0142()->id, ['type' => 'invoice', 'occurred_on' => '2026-10-26', 'counterparty' => 'Studio Uno', 'amount' => 42_000, 'tax_withheld' => 0, 'invoice_number' => '0142'])
        ->assertSessionHasNoErrors();

    expect(fn () => Transaction::query()->create(['type' => TransactionType::Invoice, 'occurred_on' => '2026-10-27', 'counterparty' => 'X', 'amount' => 1, 'invoice_number' => '0142']))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('money in from the bank for an unpaid invoice marks it paid on import', function () {
    $count = Transaction::query()->count();
    $statement = UploadedFile::fake()->createWithContent('statement.csv', implode("\n", [
        'Date,Description,Amount',
        '2026-10-27,INSTAPAY STUDIO UNO,38000', // invoice 0142 less 5% withheld
    ]));

    $this->post('/transactions/import', ['statement' => $statement]);
    $this->get('/transactions/import')->assertInertia(fn (Assert $page) => $page
        ->where('summary.settles', 1)
        ->where('summary.payments', 0)
        ->where('rows.0.status', 'settles')
        ->where('rows.0.reason', 'Pays invoice 0142 of Oct 26')
        ->where('rows.0.withheld', 2000));

    $this->post('/transactions/import/confirm', ['answers' => []])->assertInertiaFlash('toast', '1 invoice marked paid.');

    expect(invoice0142())->invoice_paid->toBeTrue()->tax_withheld->toBe('2000.00')
        ->and(Transaction::query()->count())->toBe($count);
});
