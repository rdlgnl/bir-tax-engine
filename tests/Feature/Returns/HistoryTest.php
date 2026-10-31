<?php

use App\Returns\Models\Filing;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
});

test('history lists every return, newest first', function () {
    $this->get('/history')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('history/index')
        ->has('entries', 4)
        ->where('entries.0', [
            'year' => 2026, 'quarter' => 4, 'form' => '1701-MS', 'title' => 'Annual · Income tax', 'status' => 'open',
            'amount' => 6100, 'due_date' => '2027-04-15', 'filed_on' => null, 'paid_on' => null,
        ])
        ->where('entries.1.status', 'due')
        ->where('entries.1.amount', 14000)
        ->where('entries.2.status', 'filed')
        ->where('entries.2.amount', 4000)
        ->where('entries.2.paid_on', '2026-08-14')
        // Opens on the latest filed return.
        ->where('detail.entry.quarter', 2)
        ->where('detail.filing.reference_number', 'EBIR-2026-Q2')
        ->where('detail.filing.frozen', true)
        ->where('detail.drift', null));
});

test('history shows any return in full', function () {
    $this->get('/history?year=2026&quarter=3&form=1701Q')->assertInertia(fn (Assert $page) => $page
        ->where('detail.entry.status', 'due')
        ->where('detail.filing', null)
        ->where('detail.layout.payable', 14000));
});

test('a filed return keeps its figures when records change later', function () {
    // A Q2 payment recorded after Q2 was filed, bypassing the filed-quarter lock.
    Transaction::factory()->on('2026-06-20')->create(['amount' => 50_000]);

    $this->get('/history?year=2026&quarter=2&form=1701Q')->assertInertia(fn (Assert $page) => $page
        ->where('detail.layout.payable', 4000)
        ->where('detail.drift', ['payable_now' => 8000, 'difference' => 4000]));
});

test('returns filed before snapshots existed fall back to today’s figures', function () {
    Filing::query()->update(['snapshot' => null]);

    $this->get('/history?year=2026&quarter=2&form=1701Q')->assertInertia(fn (Assert $page) => $page
        ->where('detail.layout.payable', 4000)
        ->where('detail.filing.frozen', false));
});

test('history filters are validated', function () {
    $this->get('/history?form=9999')->assertSessionHasErrors('form');
});
