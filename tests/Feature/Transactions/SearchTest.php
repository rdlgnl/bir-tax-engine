<?php

use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
});

test('a search finds the client’s records in the year, with what they add up to', function () {
    $this->get('/transactions/search?search=Lumen&type=payment')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('transactions/search')
        ->has('results', 4)
        ->where('results.0.occurred_on', '2026-10-20')
        ->where('summary', ['count' => 4, 'billed' => 170000, 'spent' => 0])
        ->where('filters.period', 'year')
        ->where('filters.from', '2026-01-01')
        ->where('filters.to', '2026-12-31'));
});

test('quick filters count and find what needs fixing', function () {
    // Recorded today for February, after Q1 was filed on May 12.
    Transaction::factory()->on('2026-02-20')->create(['counterparty' => 'Late entry']);

    $this->get('/transactions/search')->assertInertia(fn (Assert $page) => $page
        ->where('flagCounts.missing_2307', 2)
        ->where('flagCounts.after_filing', 1));

    $this->get('/transactions/search?flag=missing_2307')->assertInertia(fn (Assert $page) => $page
        ->where('results.0.counterparty', 'Lumen Digital')
        ->where('results.1.counterparty', 'Acme Co.'));
    $this->get('/transactions/search?flag=after_filing')->assertInertia(fn (Assert $page) => $page
        ->has('results', 1)
        ->where('results.0.counterparty', 'Late entry'));
});

test('the period can be this quarter or any dates', function () {
    $this->get('/transactions/search?period=quarter')->assertInertia(fn (Assert $page) => $page
        ->where('filters.from', '2026-10-01')
        ->has('results', 4));

    $this->get('/transactions/search?period=custom&from=2026-07-01&to=2026-07-31')->assertInertia(fn (Assert $page) => $page
        ->has('results', 2));

    $this->get('/transactions/search?period=custom&from=2026-07-31&to=2026-07-01')->assertSessionHasErrors('to');
});

test('the export is exactly what the filters found', function () {
    $response = $this->get('/transactions/export?search=Lumen&type=payment')->assertOk();

    expect($response->headers->get('Content-Disposition'))->toContain('records-2026-01-01-to-2026-12-31.csv');

    $lines = array_values(array_filter(explode("\n", substr($response->getContent(), 3))));
    expect($lines)->toHaveCount(5)
        ->and($lines[1])->toContain('Lumen Digital');
});

test('the Transactions list still filters the same way', function () {
    $this->get('/transactions?search=Acme')->assertInertia(fn (Assert $page) => $page->has('transactions', 2));
});
