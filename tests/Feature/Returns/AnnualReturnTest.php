<?php

use App\Profiles\Models\TaxProfile;
use App\Tax\Enums\DeductionMethod;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    Storage::fake('local');
});

test('the annual return is the 1701-MS, an estimate until the year ends', function () {
    $this->seed(DemoTaxpayerSeeder::class);

    $this->get('/file/2026/4/1701-MS')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('returns/annual')
        ->where('layout.form', '1701-MS')
        ->where('dueDate', '2027-04-15')
        ->where('periodEnded', false)
        ->where('eightPercent', true)
        ->where('figures', [
            'gross' => 695000, 'less' => 250000, 'income_tax' => 35600, 'prior_payments' => 18000,
            'withheld' => 11500, 'payable' => 6100, 'overpayment' => 0,
        ])
        ->where('certificatesNotIn', [
            ['counterparty' => 'Acme Co.', 'withheld' => 1000],
            ['counterparty' => 'Lumen Digital', 'withheld' => 1500],
        ])
        ->where('alternativeForm', '1701A'));
});

test('the older annual form is still accepted, on its own prep page', function () {
    $this->seed(DemoTaxpayerSeeder::class);

    $this->get('/file/2026/4/1701A')->assertInertia(fn (Assert $page) => $page->component('returns/show'));
    $this->get('/file/2026/4/1701-MS/form')->assertInertia(fn (Assert $page) => $page
        ->where('layout.form', '1701-MS')
        ->where('alternativeForm', '1701A'));
    $this->get('/file/2026/4/1701A/form')->assertInertia(fn (Assert $page) => $page->where('alternativeForm', null));
});

test('the filled 1701-MS downloads as the BIR form', function () {
    $this->seed(DemoTaxpayerSeeder::class);

    $response = $this->get('/file/2026/4/1701-MS/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');

    expect($response->headers->get('Content-Disposition'))->toContain('1701-MS-2026-Q4.pdf');
});

test('a mixed-income earner keeps the 1701, with no other form offered', function () {
    TaxProfile::factory()->graduated(DeductionMethod::Osd)->create(['income_source' => 'both']);
    Transaction::factory()->on('2026-03-10')->create(['amount' => 300_000]);

    $this->get('/file/2026/4/1701')->assertInertia(fn (Assert $page) => $page
        ->component('returns/annual')
        ->where('alternativeForm', null));
    $this->get('/file/2026/4/1701-MS')->assertNotFound();
});

test('only one annual income tax return is marked filed, whichever form', function () {
    $this->seed(DemoTaxpayerSeeder::class);
    $this->travelTo(CarbonImmutable::parse('2027-03-01 10:00'));
    $filing = ['year' => 2026, 'quarter' => 4, 'filed_on' => '2027-03-01', 'paid' => false];

    $this->post('/filings', [...$filing, 'form' => '1701-MS'])->assertSessionHasNoErrors();
    $this->post('/filings', [...$filing, 'form' => '1701A'])->assertSessionHasErrors('form');
});

test('the year’s 2307 scans are bundled for eAFS', function () {
    $this->seed(DemoTaxpayerSeeder::class);
    $this->post('/certificates', [
        'payor_name' => 'Acme Co.', 'payor_tin' => '008-123-456-000', 'year' => 2026, 'quarter' => 3,
        'amount_paid' => '20000', 'tax_withheld' => '1000', 'atc_code' => 'WI010',
        'file' => UploadedFile::fake()->createWithContent('acme.pdf', '%PDF-1.4 acme'),
    ])->assertSessionHasNoErrors();

    $this->get('/file/2026/4/1701-MS')->assertInertia(fn (Assert $page) => $page->where('scans', 1));

    $response = $this->get('/file/2026/eafs')->assertOk()->assertDownload('2307s-2026-for-eafs.zip');
    $zip = new ZipArchive;
    $zip->open($response->baseResponse->getFile()->getPathname());

    expect($zip->numFiles)->toBe(1)
        ->and($zip->getNameIndex(0))->toStartWith('2307-2026-Q3-acme-co-')
        ->and($zip->getFromIndex(0))->toBe('%PDF-1.4 acme');
    $zip->close();
});

test('with no scans there is nothing to bundle', function () {
    $this->seed(DemoTaxpayerSeeder::class);

    $this->get('/file/2026/eafs')->assertNotFound();
});
