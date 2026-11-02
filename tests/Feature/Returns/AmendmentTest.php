<?php

use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\OfficialForms;
use App\Returns\Models\Filing;
use App\Returns\Services\Amendments;
use App\Returns\Services\ReturnForm;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
    $this->travelTo(CarbonImmutable::parse('2026-11-10 09:00'));
});

/** Design 9-01: Juan finds a ₱20,000 payment from June after filing Q2. */
function lateFind(): void
{
    test()->post('/transactions', [
        'type' => 'payment', 'occurred_on' => '2026-06-28', 'counterparty' => 'Kape Collective', 'amount' => '20000', 'tax_withheld' => '0',
    ])->assertSessionHasNoErrors();
}

test('a filed quarter that changed needs an amendment for the difference, with interest only', function () {
    lateFind();

    $amendment = app(Amendments::class)->for(TaxProfile::current(), 2026, 2);

    expect($amendment)->toMatchArray([
        'number' => 1, 'filed_on' => '2026-08-14', 'due_date' => '2026-08-17', 'on_time' => true,
        'reason' => 'You added a ₱20,000 payment from Jun 28 after Q2 was filed.',
        'filed' => ['gross' => 400000.0, 'reduction' => 250000.0, 'income_tax' => 12000.0, 'withheld' => 8000.0, 'due' => 4000.0],
        'amended' => ['gross' => 420000.0, 'reduction' => 250000.0, 'income_tax' => 13600.0, 'withheld' => 8000.0, 'due' => 5600.0],
        'paid' => 4000.0, 'extra' => 1600.0, 'overpaid' => 0.0,
    ])->and($amendment['penalties'])->toMatchArray(['surcharge' => 0.0, 'interest' => 22.36, 'days' => 85, 'total' => 22.36])
        ->and(array_column(app(Amendments::class)->needed(TaxProfile::current()), 'quarter'))->toBe([2]);
});

test('nothing needs amending while the records match what was filed', function () {
    expect(app(Amendments::class)->needed(TaxProfile::current()))->toBe([]);
    $this->get('/file/2026/2/1701Q/amend')->assertRedirect('/history?year=2026&quarter=2&form=1701Q');
});

test('the amended 1701Q credits what the original paid and adds only the interest', function () {
    lateFind();
    $amendments = app(Amendments::class);
    $snapshot = $amendments->snapshot(TaxProfile::current(), $amendments->for(TaxProfile::current(), 2026, 2));
    $layout = app(ReturnForm::class)->layout($snapshot);

    // As the form has it, in whole pesos.
    expect($layout)->toMatchArray(['payable' => 1600.0, 'total_payable' => 1622.0])
        ->and(app(OfficialForms::class)->values($snapshot))->toMatchArray([
            '3.yes' => true, '3.no' => false, '59A' => 4000, '62A' => 12000, '63A' => 1600,
            '64A' => 0, '65A' => 22, '67A' => 22, '68A' => 1622, '31' => 1622,
        ]);

    $this->get('/file/2026/2/1701Q/amend')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('returns/amend')
        ->where('amendment.extra', 1600)
        ->where('layout.total_payable', 1622)
        // Q3 stays ₱14,000: its running total takes in the sale and, once paid, the extra for Q2.
        ->where('next', ['quarter' => 3, 'due' => 14000]));

    $response = $this->get('/file/2026/2/1701Q/amend/pdf')->assertOk();
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

test('filing the amendment keeps the original, and Q3 credits only the tax it paid', function () {
    lateFind();
    $filing = [
        'year' => 2026, 'quarter' => 2, 'form' => '1701Q', 'amendment' => true, 'filed_on' => '2026-11-10',
        'reference_number' => 'EBIR-2026-Q2-A', 'paid' => true, 'amount_paid' => '1622.36', 'paid_on' => '2026-11-10', 'payment_channel' => 'online',
    ];

    $this->post('/filings', $filing)->assertSessionHasNoErrors();

    $filings = Filing::query()->where('quarter', 2)->orderBy('amendment')->get();
    expect($filings->pluck('amendment')->all())->toBe([0, 1])
        ->and($filings[0]->snapshot['figures']['income_tax'])->toBe(12000)
        ->and($filings[1]->snapshot['amendment'])->toMatchArray(['number' => 1, 'previously_paid' => 4000])
        ->and($filings[1]->taxPaid())->toBe(1600.0)
        ->and(app(Amendments::class)->for(TaxProfile::current(), 2026, 2))->toBeNull();

    $q3 = app(TaxCalculationService::class)->forYear(TaxProfile::current(), 2026)['quarters'][3];
    expect($q3)->toMatchArray(['prior_payments' => 5600.0, 'due' => 14000.0]);

    // Nothing more to amend, so a second amendment is turned away.
    $this->post('/filings', $filing)->assertSessionHasErrors('form');

    $this->get('/history?year=2026&quarter=2&form=1701Q')->assertInertia(fn (Assert $page) => $page
        ->where('detail.amendment', null)
        ->where('detail.drift', null)
        ->where('detail.amended.0.number', 1));
});

test('Home and the notifications point to the amendment', function () {
    lateFind();

    $this->get('/home')->assertInertia(fn (Assert $page) => $page
        ->where('amendments', [['year' => 2026, 'quarter' => 2, 'form' => '1701Q', 'extra' => 1600]]));
    $this->get('/notifications')->assertInertia(fn (Assert $page) => $page
        ->where('new', fn ($items) => collect($items)->contains(fn ($item) => $item['kind'] === 'amend'
            && $item['url'] === '/file/2026/2/1701Q/amend')));
});

test('an expense recorded late after the original was late adds the surcharge too', function () {
    Filing::query()->where('quarter', 2)->update(['filed_on' => '2026-09-01']);
    lateFind();

    $amendment = app(Amendments::class)->for(TaxProfile::current(), 2026, 2);

    expect($amendment['on_time'])->toBeFalse()
        ->and($amendment['penalties'])->toMatchArray(['surcharge' => 160.0, 'interest' => 22.36]);
});

test('editing a sale moves the amendment the other way, to an overpayment', function () {
    $sale = Transaction::query()->whereDate('occurred_on', '2026-05-28')->sole();
    $this->put("/transactions/{$sale->id}", [
        'type' => 'payment', 'occurred_on' => '2026-05-28', 'counterparty' => $sale->counterparty, 'amount' => '30000', 'tax_withheld' => '0',
    ])->assertSessionHasNoErrors();

    $amendment = app(Amendments::class)->for(TaxProfile::current(), 2026, 2);

    expect($amendment)->toMatchArray(['extra' => 0.0, 'overpaid' => 1600.0, 'penalties' => null])
        ->and($amendment['reason'])->toBe('You changed a ₱30,000 payment from May 28 after Q2 was filed.');
});
