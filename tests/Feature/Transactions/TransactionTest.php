<?php

use App\Profiles\Models\TaxProfile;
use App\Returns\Models\Filing;
use App\Transactions\Enums\CertificateStatus;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
});

function payment(array $overrides = []): array
{
    return [
        'type' => 'payment',
        'occurred_on' => '2026-10-27',
        'counterparty' => 'Lumen Digital',
        'amount' => '30000',
        'tax_withheld' => '1500',
        ...$overrides,
    ];
}

test('the list shows each entry with its tax effect and 2307 status', function () {
    $this->get('/transactions')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('transactions/index')
        ->where('yearGross', 695000)
        ->where('filedQuarters', [1, 2])
        ->has('transactions', 18)
        ->where('transactions.0.counterparty', 'Studio Uno')
        ->where('transactions.0.invoice_number', '0142')
        ->where('transactions.0.tax_effect', 3200)
        ->where('transactions.1.type', 'payment')
        ->where('transactions.1.certificate_status', 'pending'));
});

test('the list filters by search and type', function () {
    $this->get('/transactions?search=acme')->assertInertia(fn (Assert $page) => $page->has('transactions', 2));
    $this->get('/transactions?type=expense')->assertInertia(fn (Assert $page) => $page->has('transactions', 1));
    $this->get('/transactions?type=withheld&quarter=3')->assertInertia(fn (Assert $page) => $page->has('transactions', 2));
    $this->get('/transactions?search=50000')->assertInertia(fn (Assert $page) => $page->has('transactions', 2));
});

test('a payment with tax withheld is saved', function () {
    $this->from('/transactions')
        ->post('/transactions', payment())
        ->assertRedirect('/transactions')
        ->assertSessionHasNoErrors();

    $saved = Transaction::query()->latest('id')->first();
    expect($saved->amount)->toBe('30000.00')
        ->and($saved->tax_withheld)->toBe('1500.00')
        ->and($saved->certificate_received_on)->toBeNull();
});

test('an invoice and an expense keep only their own fields', function () {
    $this->post('/transactions', [
        ...payment(), 'type' => 'invoice', 'invoice_number' => '0143', 'invoice_paid' => false, 'category' => 'rent',
    ])->assertSessionHasNoErrors();

    $invoice = Transaction::query()->latest('id')->first();
    expect($invoice->invoice_number)->toBe('0143')->and($invoice->category)->toBeNull();

    $this->post('/transactions', [
        ...payment(), 'type' => 'expense', 'category' => 'software', 'has_valid_invoice' => true, 'invoice_number' => 'OR-881',
    ])->assertSessionHasNoErrors();

    // An expense keeps the supplier's receipt number, for the books.
    $expense = Transaction::query()->latest('id')->first();
    expect($expense->tax_withheld)->toBe('0.00')
        ->and($expense->invoice_number)->toBe('OR-881')
        ->and($expense->has_valid_invoice)->toBeTrue();
});

test('entries are validated', function (array $data, string $field, ?string $message = null) {
    $response = $this->post('/transactions', payment($data));

    $message
        ? $response->assertSessionHasErrors([$field => $message])
        : $response->assertSessionHasErrors($field);
})->with([
    'no amount' => [['amount' => ''], 'amount', 'Enter the full amount before anything was deducted.'],
    'zero amount' => [['amount' => '0'], 'amount'],
    'withheld more than billed' => [['tax_withheld' => '40000'], 'tax_withheld'],
    'in the future' => [['occurred_on' => '2026-12-01'], 'occurred_on'],
    'no client' => [['counterparty' => ''], 'counterparty'],
    'invoice without a number' => [['type' => 'invoice', 'invoice_number' => ''], 'invoice_number'],
    'expense without a category' => [['type' => 'expense'], 'category'],
    'unknown type' => [['type' => 'gift'], 'type'],
]);

test('a filed quarter can still change; its return is then amended', function () {
    $this->post('/transactions', payment(['occurred_on' => '2026-05-20']))->assertSessionHasNoErrors();

    $filed = Transaction::query()->whereDate('occurred_on', '2026-05-28')->first();
    $this->put("/transactions/{$filed->id}", payment(['occurred_on' => '2026-05-28', 'amount' => '61000']))->assertSessionHasNoErrors();

    expect($filed->fresh()->amount)->toBe('61000.00');
});

test('nothing in a year whose annual return is filed can be added, moved, changed or deleted', function () {
    Filing::factory()->create(['year' => 2026, 'quarter' => 4, 'form' => '1701-MS', 'filed_on' => '2026-10-27']);
    $filed = Transaction::query()->whereDate('occurred_on', '2026-05-28')->first();

    $this->post('/transactions', payment(['occurred_on' => '2026-05-20']))
        ->assertSessionHasErrors(['occurred_on' => "Your 2026 annual return is already filed. Changing that year needs an amended annual return, which isn't available yet."]);
    $this->put("/transactions/{$filed->id}", payment(['occurred_on' => '2026-10-01']))->assertSessionHasErrors('occurred_on');
    $this->delete("/transactions/{$filed->id}")->assertSessionHasErrors('transaction');

    expect($filed->fresh()->occurred_on->toDateString())->toBe('2026-05-28');
});

test('an open-quarter entry can be edited and deleted', function () {
    $entry = Transaction::query()->whereDate('occurred_on', '2026-10-20')->first();

    $this->put("/transactions/{$entry->id}", payment(['amount' => '32000', 'occurred_on' => '2026-10-20']))
        ->assertSessionHasNoErrors();
    expect($entry->fresh()->amount)->toBe('32000.00');

    $this->delete("/transactions/{$entry->id}")->assertSessionHasNoErrors();
    expect(Transaction::find($entry->id))->toBeNull();
});

test('the preview shows the tax effect before saving', function () {
    $this->postJson('/transactions/preview', payment(['counterparty' => 'New Client']))
        ->assertOk()
        ->assertJson(['quarter' => 4, 'tax_effect' => 2400, 'credit' => 1500, 'filed' => false, 'duplicate' => null, 'unusual_multiple' => null]);
});

test('the preview warns about a likely duplicate and an extra zero', function () {
    $this->postJson('/transactions/preview', payment(['occurred_on' => '2026-10-22']))
        ->assertJsonPath('duplicate.counterparty', 'Lumen Digital')
        ->assertJsonPath('duplicate.occurred_on', '2026-10-20');

    // The usual (median) payment is ₱40,000.
    $this->postJson('/transactions/preview', payment(['amount' => '400000', 'counterparty' => 'Kape Collective']))
        ->assertJsonPath('unusual_multiple', 10)
        ->assertJsonPath('tax_effect', 32000);
});

test('the preview flags a filed quarter and rejects nonsense', function () {
    $this->postJson('/transactions/preview', payment(['occurred_on' => '2026-04-02']))->assertJsonPath('filed', true);
    $this->postJson('/transactions/preview', ['type' => 'payment'])->assertUnprocessable();
});

test('a client’s 2307 can be marked received', function () {
    $acme = Transaction::query()->where('counterparty', 'Acme Co.')->whereDate('occurred_on', '2026-08-19')->first();
    expect($acme->certificateStatus(now()))->toBe(CertificateStatus::Missing);

    $this->put("/transactions/{$acme->id}/certificate", ['received' => true])->assertSessionHasNoErrors();

    expect($acme->fresh()->certificate_received_on->toDateString())->toBe('2026-10-27');
});

test('a 2307 can’t be tracked when nothing was withheld', function () {
    $plain = Transaction::query()->where('tax_withheld', 0)->where('type', 'payment')->first();

    $this->put("/transactions/{$plain->id}/certificate", ['received' => true])->assertSessionHasErrors('received');
});

test('the add form gets its context from shared props', function () {
    $this->get('/home')->assertInertia(fn (Assert $page) => $page
        ->where('transactionForm.regime', 'eight_percent')
        ->where('transactionForm.expectedWithholdingPercent', 5)
        ->where('transactionForm.recentCounterparties', ['Studio Uno', 'Lumen Digital', 'Kape Collective'])
        ->has('transactionForm.awaitingCertificates', 2));
});

test('transactions need a finished setup', function () {
    TaxProfile::query()->update(['setup_completed_at' => null]);

    $this->post('/transactions', payment())->assertRedirect(route('welcome'));
    expect(Filing::count())->toBe(2);
});
