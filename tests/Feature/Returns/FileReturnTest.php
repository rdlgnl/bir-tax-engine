<?php

use App\Profiles\Models\TaxProfile;
use App\Returns\Models\Filing;
use App\Tax\Enums\DeductionMethod;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
});

test('file a return opens the one that is due', function () {
    $this->seed(DemoTaxpayerSeeder::class);

    $this->get('/file')->assertRedirect(route('returns.show', [2026, 3, '1701Q']));
});

test('the Q3 1701Q values match the design, top to bottom', function () {
    $this->seed(DemoTaxpayerSeeder::class);

    $this->get('/file/2026/3/1701Q')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('returns/show')
        ->where('layout.period', '2026 · 3rd')
        ->where('layout.method', '8% option')
        ->where('layout.taxpayer', ['name' => 'DELA CRUZ, JUAN SANTOS', 'tin' => '123-456-789-000', 'rdo' => '028', 'atc' => 'II015'])
        ->where('layout.lines', [
            ['label' => 'Sales/receipts, this quarter', 'amount' => 200000, 'kind' => 'amount'],
            ['label' => 'Sales/receipts, previous quarters', 'amount' => 400000, 'kind' => 'amount'],
            ['label' => 'Less: allowable reduction from gross sales', 'amount' => 250000, 'kind' => 'subtract'],
            ['label' => 'Taxable income to date', 'amount' => 350000, 'kind' => 'total'],
            ['label' => 'Tax due (8%)', 'amount' => 28000, 'kind' => 'total'],
            ['label' => 'Less: tax paid in previous quarters', 'amount' => 4000, 'kind' => 'subtract'],
            ['label' => 'Less: creditable tax withheld, previous quarters', 'amount' => 8000, 'kind' => 'subtract'],
            ['label' => 'Less: creditable tax withheld, this quarter', 'amount' => 2000, 'kind' => 'subtract'],
            ['label' => 'Tax payable', 'amount' => 14000, 'kind' => 'total'],
        ])
        ->where('layout.payable', 14000)
        ->where('dueDate', '2026-11-16')
        ->where('periodEnded', true)
        ->where('missingCertificates.0.counterparty', 'Acme Co.')
        ->where('otherForms', []));
});

test('a quarter with more credit than tax shows an overpayment', function () {
    $this->seed(DemoTaxpayerSeeder::class);

    $this->get('/file/2026/1/1701Q/form')->assertInertia(fn (Assert $page) => $page
        ->where('layout.lines.8', ['label' => 'Overpayment', 'amount' => 3000, 'kind' => 'total'])
        ->where('filedOn', '2026-05-12'));
});

test('graduated taxpayers also file a 2551Q', function () {
    TaxProfile::factory()->graduated(DeductionMethod::Osd)->create(['profession' => 'cpa_lawyer']);
    Transaction::factory()->on('2026-08-10')->create(['amount' => 200_000]);

    $this->get('/file/2026/3/1701Q')->assertInertia(fn (Assert $page) => $page
        ->where('layout.method', 'graduated rates, optional standard deduction')
        ->where('layout.taxpayer.atc', 'II014')
        ->where('layout.lines.2.label', 'Less: optional standard deduction (40%)')
        ->where('otherForms', ['2551Q']));

    $this->get('/file/2026/3/2551Q')->assertInertia(fn (Assert $page) => $page
        ->where('layout.title', 'Quarterly Percentage Tax Return')
        ->where('layout.taxpayer.atc', 'PT010')
        ->where('layout.payable', 6000)
        ->where('dueDate', '2026-10-26'));
});

test('each filled form points to the other return due with it', function () {
    TaxProfile::factory()->graduated(DeductionMethod::Osd)->create(['profession' => 'cpa_lawyer']);
    Transaction::factory()->on('2026-08-10')->create(['amount' => 200_000]);

    // ₱200,000 less 40% is ₱120,000 taxable, under the ₱250,000 that graduated rates exempt.
    $this->get('/file/2026/3/2551Q/form')->assertInertia(fn (Assert $page) => $page
        ->where('alsoFile', [['form' => '1701Q', 'payable' => 0]]));

    $this->get('/file/2026/3/1701Q/form')->assertInertia(fn (Assert $page) => $page
        ->where('layout.regime', 'graduated')
        ->where('layout.deduction_method', 'osd')
        ->where('alsoFile', [['form' => '2551Q', 'payable' => 6000]]));
});

test('on 8% there is no other return to file', function () {
    $this->seed(DemoTaxpayerSeeder::class);

    $this->get('/file/2026/3/1701Q/form')->assertInertia(fn (Assert $page) => $page->where('alsoFile', []));
});

test('forms a taxpayer doesn’t file, or periods not started, don’t exist', function (string $url) {
    $this->seed(DemoTaxpayerSeeder::class);

    $this->get($url)->assertNotFound();
})->with([
    '2551Q on 8%' => ['/file/2026/3/2551Q'],
    'annual form for a quarter' => ['/file/2026/3/1701A'],
    'future quarter' => ['/file/2027/1/1701Q'],
]);

test('an open quarter can be previewed but not marked filed yet', function () {
    $this->seed(DemoTaxpayerSeeder::class);

    $this->get('/file/2026/4/1701A')->assertInertia(fn (Assert $page) => $page
        ->where('periodEnded', false)
        ->where('layout.title', 'Annual Income Tax Return')
        ->where('layout.lines.0', ['label' => 'Sales/receipts for the year', 'amount' => 695000, 'kind' => 'amount']));
});

test('a filed return sends you to its history', function () {
    $this->seed(DemoTaxpayerSeeder::class);

    $this->get('/file/2026/2/1701Q')->assertRedirect(route('history', ['year' => 2026, 'quarter' => 2, 'form' => '1701Q']));
});

test('the filled form downloads as a PDF', function () {
    $this->seed(DemoTaxpayerSeeder::class);

    $download = $this->get('/file/2026/3/1701Q/pdf')->assertOk();
    expect($download->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($download->headers->get('Content-Disposition'))->toContain('attachment; filename=1701Q-2026-Q3.pdf')
        ->and(substr($download->getContent(), 0, 4))->toBe('%PDF');

    $print = $this->get('/file/2026/3/1701Q/pdf?print=1')->assertOk();
    expect($print->headers->get('Content-Disposition'))->toContain('inline');
});

test('marking a return filed freezes its figures', function () {
    $this->seed(DemoTaxpayerSeeder::class);

    $this->post('/filings', [
        'year' => 2026, 'quarter' => 3, 'form' => '1701Q', 'filed_on' => '2026-10-27',
        'paid' => true, 'amount_paid' => '14000', 'paid_on' => '2026-10-27',
    ])->assertSessionHasNoErrors();

    $snapshot = Filing::query()->where('quarter', 3)->sole()->snapshot;

    expect($snapshot['figures']['due'])->toBe(14000)
        ->and($snapshot['rule_pack'])->toBe('2026.11')
        ->and($snapshot['rules'])->toBe(['IT-8PCT-PURE v2', 'CWT-CREDIT v1', 'DL-1701Q v1']);
});
