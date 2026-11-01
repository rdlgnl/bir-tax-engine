<?php

use App\Compliance\Models\Certificate;
use App\Compliance\Services\TaxObligations;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
});

function certificate(string $payor, int $quarter, float $withheld): Certificate
{
    return Certificate::query()->create([
        'payor_name' => $payor, 'year' => 2026, 'quarter' => $quarter, 'amount_paid' => $withheld * 20, 'tax_withheld' => $withheld,
    ]);
}

function acme(): Transaction
{
    return Transaction::query()->where('counterparty', 'Acme Co.')->whereDate('occurred_on', '2026-08-19')->firstOrFail();
}

test('a 2307 saved before its payment links once the payment is recorded', function () {
    $certificate = certificate('Bayani Foods Inc.', 4, 500);

    $payment = Transaction::factory()->on('2026-10-25')->create(['counterparty' => 'Bayani Foods', 'amount' => 10_000, 'tax_withheld' => 500])->refresh();

    expect($payment->certificate_id)->toBe($certificate->id)
        ->and($payment->certificate_received_on?->toDateString())->toBe('2026-10-27');
    $this->assertDatabaseHas('notices', ['kind' => 'certificate', 'title' => '2307 from Bayani Foods Inc. matched']);
});

test('a 2307 that didn’t match links once the payment is corrected, and comes undone if it stops matching', function () {
    $certificate = certificate('Acme Co.', 3, 1_200);
    expect(acme()->certificate_id)->toBeNull();

    acme()->update(['tax_withheld' => 1_200]);
    expect(acme()->certificate_id)->toBe($certificate->id);

    acme()->update(['tax_withheld' => 900]);
    expect(acme())->certificate_id->toBeNull()->certificate_received_on->toBeNull();
});

test('deleting one of the payments a 2307 covers unlinks the rest', function () {
    // Acme's Q3 2307 covers two payments: ₱1,000 withheld, then ₱500.
    $certificate = certificate('Acme Co.', 3, 1_500);
    $second = Transaction::factory()->on('2026-09-10')->create(['counterparty' => 'Acme Co.', 'amount' => 10_000, 'tax_withheld' => 500]);
    expect($certificate->transactions()->count())->toBe(2);

    $second->delete();
    expect($certificate->transactions()->count())->toBe(0)
        ->and(acme()->certificate_received_on)->toBeNull();
});

test('a 2307 that doesn’t match shows on the compliance page and can be linked by hand', function () {
    $certificate = certificate('Acme Co.', 3, 1_200);
    $obligations = app(TaxObligations::class);
    $rows = fn () => collect($obligations->certificates(Transaction::query()->whereYear('occurred_on', 2026)->get(), [], CarbonImmutable::now())['rows']);

    expect($rows()->first(fn (array $r) => $r['counterparty'] === 'Acme Co.' && $r['quarter'] === 3)['unmatched'])
        ->toBe(['id' => $certificate->id, 'tax_withheld' => 1200.0]);

    $this->post("/certificates/{$certificate->id}/link")->assertRedirect();

    expect(acme())->certificate_id->toBe($certificate->id)
        ->and($certificate->refresh()->linked_by_hand)->toBeTrue()
        ->and($rows()->first(fn (array $r) => $r['counterparty'] === 'Acme Co.' && $r['quarter'] === 3))
        ->toMatchArray(['status' => 'received', 'unmatched' => null]);

    // Linked by hand, it stays linked through edits, until the payment moves quarter.
    acme()->update(['tax_withheld' => 1_100]);
    expect(acme()->certificate_id)->toBe($certificate->id);

    $payment = acme();
    $payment->update(['occurred_on' => '2026-10-01']);
    expect($payment->refresh()->certificate_id)->toBeNull();
});
