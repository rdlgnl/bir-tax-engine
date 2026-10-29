<?php

use App\Returns\Models\Filing;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
});

function q3Filing(array $overrides = []): array
{
    return [
        'year' => 2026, 'quarter' => 3, 'form' => '1701Q', 'filed_on' => '2026-10-27',
        'reference_number' => 'EFPS-123', 'paid' => true, 'amount_paid' => '14000',
        'paid_on' => '2026-10-27', 'payment_channel' => 'online',
        ...$overrides,
    ];
}

test('marking Q3 filed and paid clears the next payment', function () {
    $this->post('/filings', q3Filing())->assertSessionHasNoErrors();

    expect(Filing::query()->where('quarter', 3)->first())
        ->amount_paid->toBe('14000.00')
        ->reference_number->toBe('EFPS-123');

    $this->get('/home')->assertInertia(fn (Assert $page) => $page
        ->where('nextPayment.state', 'clear')
        ->where('nextPayment.next_deadline', '2027-04-15'));
});

test('a return can be filed now and paid later', function () {
    $this->post('/filings', q3Filing(['paid' => false, 'amount_paid' => '999', 'paid_on' => '2026-10-27']))
        ->assertSessionHasNoErrors();

    $filing = Filing::query()->where('quarter', 3)->first();
    expect($filing->amount_paid)->toBeNull()->and($filing->isPaid())->toBeFalse();
});

test('a quarter can’t be filed before it ends, or twice', function () {
    $this->post('/filings', q3Filing(['quarter' => 4, 'form' => '1701A']))->assertSessionHasErrors('quarter');
    $this->post('/filings', q3Filing(['quarter' => 2]))->assertSessionHasErrors(['form' => 'This return is already marked as filed.']);
});

test('filing details are validated', function (array $data, string $field) {
    $this->post('/filings', q3Filing($data))->assertSessionHasErrors($field);
})->with([
    'filed in the future' => [['filed_on' => '2026-12-01'], 'filed_on'],
    'unknown form' => [['form' => '9999'], 'form'],
    'paid with no amount' => [['amount_paid' => ''], 'amount_paid'],
    'unknown channel' => [['payment_channel' => 'gcash-direct'], 'payment_channel'],
]);

test('a return filed without payment can be marked paid once', function () {
    $this->post('/filings', q3Filing(['paid' => false]))->assertSessionHasNoErrors();
    $filing = Filing::query()->where('quarter', 3)->first();

    $this->patch("/filings/{$filing->id}", ['amount_paid' => '14000', 'paid_on' => '2026-10-27', 'payment_channel' => 'bank'])
        ->assertSessionHasNoErrors();
    expect($filing->fresh()->isPaid())->toBeTrue();

    $this->patch("/filings/{$filing->id}", ['amount_paid' => '1', 'paid_on' => '2026-10-27'])
        ->assertSessionHasErrors(['amount_paid' => 'This return is already marked as paid.']);
});
