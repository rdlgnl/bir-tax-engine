<?php

use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\OfficialForms;
use App\Returns\Models\Filing;
use App\Returns\Services\ReturnForm;
use App\Tax\Services\Penalties;
use App\Tax\Services\TaxCalculationService;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('a micro or small taxpayer pays a 10% surcharge and 6% interest a year', function () {
    $penalties = app(Penalties::class);

    // Design 9-02: ₱14,000 due Mon, Nov 16, paid Thu, Nov 19.
    expect($penalties->late(14_000, CarbonImmutable::parse('2026-11-16'), CarbonImmutable::parse('2026-11-19'), 695_000))->toBe([
        'surcharge' => 1400.0, 'surcharge_rate' => 0.10, 'interest' => 6.9, 'interest_rate' => 0.06, 'days' => 3,
        'compromise' => 0.0, 'total' => 1406.9, 'due_date' => '2026-11-16', 'pay_on' => '2026-11-19',
    ])
        ->and($penalties->weeklyInterest(14_000, CarbonImmutable::parse('2026-11-16'), 695_000))->toBe(16.11)
        // Design 9-01: an amendment of an on-time return owes interest only.
        ->and($penalties->late(1_600, CarbonImmutable::parse('2026-08-17'), CarbonImmutable::parse('2026-11-10'), 695_000, surcharge: false))
        ->toMatchArray(['surcharge' => 0.0, 'interest' => 22.36, 'days' => 85, 'total' => 22.36]);
});

test('larger taxpayers pay the full 25% surcharge and 12% interest', function () {
    expect(app(Penalties::class)->late(100_000, CarbonImmutable::parse('2026-11-16'), CarbonImmutable::parse('2026-12-16'), 25_000_000))
        ->toMatchArray(['surcharge' => 25000.0, 'interest' => 986.3, 'days' => 30, 'total' => 25986.3]);
});

test('the reduced rates apply only to returns due from April 27, 2024 (RR 6-2024)', function () {
    $penalties = app(Penalties::class);

    expect($penalties->late(10_000, CarbonImmutable::parse('2024-04-15'), CarbonImmutable::parse('2024-05-15'), 695_000))
        ->toMatchArray(['surcharge_rate' => 0.25, 'interest_rate' => 0.12])
        ->and($penalties->late(10_000, CarbonImmutable::parse('2024-05-15'), CarbonImmutable::parse('2024-06-15'), 695_000))
        ->toMatchArray(['surcharge_rate' => 0.10, 'interest_rate' => 0.06]);
});

test('the class the BIR assigned for the year decides the rates over the sales estimate', function () {
    $this->seed(DemoTaxpayerSeeder::class);
    $penalties = app(Penalties::class);

    // ₱695,000 of sales points to micro, but the BIR classed the taxpayer medium for 2026.
    $this->put('/profile/classification', ['year' => 2026, 'classification' => 'medium'])->assertRedirect();

    expect($penalties->late(10_000, CarbonImmutable::parse('2026-11-16'), CarbonImmutable::parse('2026-11-19'), 695_000))
        ->toMatchArray(['surcharge_rate' => 0.25, 'interest_rate' => 0.12]);

    // Blank goes back to the estimate.
    $this->put('/profile/classification', ['year' => 2026, 'classification' => null])->assertRedirect();

    expect($penalties->late(10_000, CarbonImmutable::parse('2026-11-16'), CarbonImmutable::parse('2026-11-19'), 695_000))
        ->toMatchArray(['surcharge_rate' => 0.10]);
    $this->get('/profile')->assertInertia(fn (Assert $page) => $page
        ->where('classification.assigned', null)
        ->where('classification.estimate.value', 'micro'));
});

test('a return prepared after its due date adds the penalties to every figure', function () {
    $this->seed(DemoTaxpayerSeeder::class);
    $this->travelTo(CarbonImmutable::parse('2026-11-19 09:00'));
    $returns = app(ReturnForm::class);
    $snapshot = $returns->snapshot(TaxProfile::current(), 2026, 3, '1701Q');
    $layout = $returns->layout($snapshot);

    expect($snapshot['penalties'])->toMatchArray(['surcharge' => 1400.0, 'interest' => 6.9, 'total' => 1406.9])
        ->and($layout['payable'])->toBe(14000.0)
        ->and($layout['total_payable'])->toBe(15406.9)
        ->and(array_slice(array_column($layout['lines'], 'label'), -3))->toBe([
            'Add: surcharge (10%)', 'Add: interest (6% a year, 3 days)', 'Total amount payable',
        ])
        ->and(app(OfficialForms::class)->values($snapshot))->toMatchArray([
            '63A' => 14000, '64A' => 1400, '65A' => 7, '67A' => 1407, '68A' => 15407,
            '28A' => 14000, '29A' => 1407, '30A' => 15407, '31' => 15407,
        ]);

    $this->get('/file/2026/3/1701Q/late')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('returns/late')
        ->where('dueDate', '2026-11-16')
        ->where('weekly', 16.11)
        ->where('layout.total_payable', 15406.9));
});

test('on time there are no penalties and no late page', function () {
    $this->seed(DemoTaxpayerSeeder::class);
    $this->travelTo(CarbonImmutable::parse('2026-11-10 09:00'));

    expect(app(ReturnForm::class)->snapshot(TaxProfile::current(), 2026, 3, '1701Q')['penalties'])->toBeNull();
    $this->get('/file/2026/3/1701Q/late')->assertRedirect('/file/2026/3/1701Q');
});

test('a late return keeps the penalties it was filed with', function () {
    $this->seed(DemoTaxpayerSeeder::class);
    $this->travelTo(CarbonImmutable::parse('2026-11-19 09:00'));

    $this->post('/filings', [
        'year' => 2026, 'quarter' => 3, 'form' => '1701Q', 'filed_on' => '2026-11-19', 'reference_number' => '',
        'paid' => true, 'amount_paid' => '15406.90', 'paid_on' => '2026-11-19', 'payment_channel' => 'online',
    ])->assertSessionHasNoErrors();

    $this->travelTo(CarbonImmutable::parse('2026-12-20 09:00'));
    $filing = Filing::query()->where('quarter', 3)->where('form', '1701Q')->sole();

    expect(app(ReturnForm::class)->snapshotOf($filing, TaxProfile::current())['penalties'])
        ->toMatchArray(['total' => 1406.9, 'pay_on' => '2026-11-19'])
        // The penalties aren't tax: the annual return credits only the ₱14,000.
        ->and($filing->taxPaid())->toBe(14000.0)
        ->and(app(TaxCalculationService::class)->forYear(TaxProfile::current(), 2026)['quarters'][4]['prior_payments'])->toBe(18000.0);
    $this->get('/file/2026/3/1701Q/late')->assertRedirect('/file/2026/3/1701Q');
});
