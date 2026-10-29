<?php

use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
});

test('compliance opens on Q3 with its checklist, as in the design', function () {
    $this->get('/compliance')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('compliance/show')
        ->where('quarter', 3)
        ->has('quarters', 4)
        ->where('checklist.done', 3)
        ->where('checklist.items.0', [
            'key' => 'income', 'label' => 'Income recorded', 'done' => true, 'state' => 'done',
            'detail' => '6 payments · ₱200,000', 'action' => 'transactions',
        ])
        ->where('checklist.items.2.label', 'Tax certificates: 1 of 2')
        ->where('checklist.items.2.state', 'warning')
        ->where('checklist.items.2.detail', 'Missing from Acme Co.')
        ->where('checklist.items.3.detail', '8% option replaces 2551Q')
        ->where('checklist.items.4.detail', '1701Q · due Nov 16')
        ->where('checklist.items.4.action', 'file')
        ->where('checklist.items.5.action', null)
        ->where('corOutdated', true));
});

test('the 2307 tracker totals match the design', function () {
    $this->get('/compliance')->assertInertia(fn (Assert $page) => $page
        ->where('certificates.credits', 11500)
        ->where('certificates.confirmed', 9000)
        ->where('certificates.waiting', 2500)
        ->has('certificates.rows', 3)
        ->where('certificates.rows.0.counterparty', 'Acme Co.')
        ->where('certificates.rows.0.status', 'missing')
        ->where('certificates.rows.1.status', 'received')
        ->where('certificates.rows.1.received_on', '2026-10-09')
        ->where('certificates.rows.2.quarter', 4)
        ->where('certificates.rows.2.status', 'pending')
        ->where('certificates.rows.2.expected_by', '2027-01-20')
        ->where('certificates.filed', ['quarters' => [1, 2], 'count' => 3, 'amount' => 8000, 'all_received' => true]));
});

test('a filed quarter shows as done and offers no actions', function () {
    $this->get('/compliance/2')->assertInertia(fn (Assert $page) => $page
        ->where('quarter', 2)
        ->where('checklist.items.4.detail', 'Filed Aug 14')
        ->where('checklist.items.5.done', true)
        ->where('checklist.items.5.action', null));
});

test('a filed but unpaid return offers mark paid', function () {
    $this->post('/filings', [
        'year' => 2026, 'quarter' => 3, 'form' => '1701Q', 'filed_on' => '2026-10-27', 'paid' => false,
    ])->assertSessionHasNoErrors();

    $this->get('/compliance/3')->assertInertia(fn (Assert $page) => $page
        ->where('checklist.items.4.done', true)
        ->where('checklist.items.5.action', 'pay')
        ->where('checklist.filing.paid_on', null));
});
