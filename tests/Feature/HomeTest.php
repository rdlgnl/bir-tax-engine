<?php

use App\Profiles\Models\TaxProfile;
use App\Returns\Models\Filing;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('home shows Q3 as next to pay, as in the design', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);

    $this->get('/home')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('home')
        ->where('taxpayer.first_name', 'Juan')
        ->where('nextPayment.state', 'due')
        ->where('nextPayment.quarter.quarter', 3)
        ->where('nextPayment.quarter.due', 14000)
        ->where('nextPayment.quarter.days_left', 20)
        ->where('focus.quarter_withheld', 2000)
        ->where('checklist.missing_certificates.0.counterparty', 'Acme Co.')
        ->where('upcoming.0', [
            'date' => '2026-11-16', 'statutory_date' => '2026-11-15', 'title' => 'Q3 1701Q · file + pay',
            'kind' => 'income_tax', 'status' => 'due', 'amount' => 14000, 'note' => null, 'tone' => 'default', 'href' => null,
        ])
        ->where('upcoming.1.title', 'Sworn declarations to clients')
        ->where('nextDeadline', ['date' => '2026-11-16', 'form' => '1701Q', 'quarter' => 3, 'amount' => 14000])
        ->has('recent', 5)
        ->where('recent.0.counterparty', 'Studio Uno'));
});

test('an unfiled quarter turns overdue the day after its deadline', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-19 08:00'));
    $this->seed(DemoTaxpayerSeeder::class);

    $this->get('/home')->assertInertia(fn (Assert $page) => $page
        ->where('nextPayment.state', 'overdue')
        ->where('nextPayment.quarter.days_left', -3));
});

test('a new user sees an estimate until income is recorded', function () {
    $this->travelTo(CarbonImmutable::parse('2026-03-10'));
    TaxProfile::factory()->create(['expected_income' => 800000]);

    $this->get('/home')->assertInertia(fn (Assert $page) => $page
        ->where('nextPayment.state', 'empty')
        ->where('nextPayment.estimate', 44000)
        ->has('recent', 0));
});

test('last year’s annual return is due in the first months', function () {
    $this->travelTo(CarbonImmutable::parse('2027-02-10'));
    $this->seed(DemoTaxpayerSeeder::class);
    Filing::factory()->quarter(3, '2026-11-16', 14000)->create();

    $this->get('/home')->assertInertia(fn (Assert $page) => $page
        ->where('nextPayment.state', 'due')
        ->where('nextPayment.year', 2026)
        ->where('nextPayment.quarter.form', '1701-MS')
        ->where('nextPayment.quarter.due', 2900)
        ->where('nextPayment.quarter.due_date', '2027-04-15'));
});

test('home shows graduated rates once 8% sales pass ₱3,000,000', function () {
    $this->travelTo(CarbonImmutable::parse('2026-04-10'));
    TaxProfile::factory()->create();
    Transaction::factory()->on('2026-03-01')->create(['amount' => 3_100_000]);

    $this->get('/home')->assertInertia(fn (Assert $page) => $page
        ->where('nextPayment.state', 'due')
        ->where('yearStatus', 'ok')
        ->where('regime', 'graduated'));
});
