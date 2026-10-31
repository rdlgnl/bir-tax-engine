<?php

use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
});

test('each client that withholds gets a declaration, due January 15', function () {
    $this->get('/sworn')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('compliance/sworn')
        ->where('declaration.year', 2026)
        ->where('declaration.name', 'JUAN SANTOS DELA CRUZ')
        ->where('declaration.vat', false)
        ->where('declaration.over_limit', false)
        ->where('declaration.fees', 'fees for my services')
        ->where('declaration.rate', 5)
        ->where('declaration.rate_without', 10)
        ->where('declaration.due', '2027-01-15')
        ->where('clients', [
            ['key' => 'acme-co', 'name' => 'Acme Co.', 'rate' => 5],
            ['key' => 'lumen-digital', 'name' => 'Lumen Digital', 'rate' => 5],
        ])
        ->where('paidInFull', ['Bayani Foods', 'Kape Collective', 'Studio Uno']));
});

test('in early January the declaration is for the year just ended', function () {
    $this->travelTo(CarbonImmutable::parse('2027-01-10 09:00'));

    $this->get('/sworn')->assertInertia(fn (Assert $page) => $page
        ->where('declaration.year', 2026)
        ->where('declaration.due', '2027-01-15'));
});

test('past ₱3,000,000 the declaration says so, and clients withhold 10%', function () {
    Transaction::factory()->on('2026-10-01')->create(['amount' => 2_500_000]);

    $this->get('/sworn')->assertInertia(fn (Assert $page) => $page->where('declaration.over_limit', true));
});

test('the declarations download as one PDF, or one client’s alone', function () {
    $all = $this->get('/sworn/2026/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $one = $this->get('/sworn/2026/pdf/acme-co')->assertOk();

    expect($all->headers->get('Content-Disposition'))->toContain('sworn-declarations-2026.pdf')
        ->and($one->headers->get('Content-Disposition'))->toContain('sworn-declaration-2026-acme-co.pdf');

    $this->get('/sworn/2026/pdf/nobody')->assertNotFound();
});

test('Upcoming links the January reminder to the declarations', function () {
    $this->get('/home')->assertInertia(fn (Assert $page) => $page
        ->where('upcoming', fn ($items) => collect($items)->contains(fn ($item) => $item['title'] === 'Sworn declarations to clients' && $item['href'] === '/sworn/2026')));
});
