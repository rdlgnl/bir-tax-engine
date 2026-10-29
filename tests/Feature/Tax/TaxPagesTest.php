<?php

use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
});

test('my tax opens on the quarter that is due, with its explanation', function () {
    $this->get('/tax')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('tax/show')
        ->where('quarter', 3)
        ->where('result.quarters.3.due', 14000)
        ->where('methodLockedOn', '2026-05-12')
        ->where('explanation.reasons.3.text', 'You chose Flat 8% on your Q1 return')
        ->where('explanation.reasons.3.source', 'filed May 12')
        ->where('explanation.rules.0.id', 'IT-8PCT-PURE')
        ->where('explanation.rules.1.id', 'CWT-CREDIT')
        ->where('explanation.rules.2.title', 'Due Nov 15, moved to Mon Nov 16')
        ->has('explanation.skipped', 2)
        ->where('explanation.warnings.0', 'No 2307 yet from Acme Co. Without it, ₱1,000 of your credit may be disallowed.'));
});

test('my tax can show any quarter', function () {
    $this->get('/tax/2')->assertInertia(fn (Assert $page) => $page->where('quarter', 2)->where('result.quarters.2.status', 'filed'));
    $this->get('/tax/5')->assertNotFound();
});

test('what-if compares methods for the numbers given', function () {
    $this->get('/tax/compare?income=800000&expenses=30')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('tax/compare')
        ->where('comparison.options.0.total', 44000)
        ->where('comparison.options.2.total', 78500)
        ->where('comparison.savings', 18500)
        ->where('nextDecisionDue', '2027-05-17'));
});

test('what-if starts from the profile’s estimate and rejects bad input', function () {
    $this->get('/tax/compare')->assertInertia(fn (Assert $page) => $page->where('comparison.income', 800000));
    $this->get('/tax/compare?expenses=150')->assertSessionHasErrors('expenses');
});
