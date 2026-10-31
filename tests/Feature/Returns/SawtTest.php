<?php

use App\Profiles\Models\TaxProfile;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
});

test('the Q3 SAWT lists the 2307s claimed, as on the design', function () {
    $this->get('/file/2026/3/sawt')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('returns/sawt')
        ->where('form', '1701Q')
        ->has('rows', 2)
        ->where('rows.0.payor', 'Acme Co.')
        ->where('rows.0.certificate', false)
        ->where('rows.1.payor', 'Lumen Digital')
        ->where('rows.1.certificate', true)
        ->where('totals', ['clients' => 2, 'income' => 40000, 'withheld' => 2000])
        ->where('fileName', '1234567890000092026'.'1701Q.DAT'));
});

test('the SAWT file has a header, a line per client and a control line', function () {
    $response = $this->get('/file/2026/3/sawt/export')->assertOk();

    expect($response->headers->get('Content-Disposition'))->toContain('1234567890000092026'.'1701Q.DAT');

    $lines = explode("\r\n", trim($response->getContent()));

    expect($lines)->toHaveCount(4)
        ->and($lines[0])->toBe('HSAWT,H1701Q,123456789,0000,"","DELA CRUZ","JUAN","SANTOS",09/2026,028')
        ->and($lines[1])->toBe('DSAWT,D1701Q,1,,0000,"ACME CO.","","","",09/2026,,5.00,20000.00,1000.00')
        ->and($lines[2])->toStartWith('DSAWT,D1701Q,2,,0000,"LUMEN DIGITAL"')
        ->and($lines[3])->toBe('CSAWT,C1701Q,123456789,0000,09/2026,40000.00,2000.00');
});

test('the client’s TIN and tax code come from their 2307', function () {
    $this->post('/certificates', [
        'payor_name' => 'Acme Co.', 'payor_tin' => '008-123-456-000', 'year' => 2026, 'quarter' => 3,
        'amount_paid' => '20000', 'tax_withheld' => '1000', 'atc_code' => 'WI010',
    ])->assertSessionHasNoErrors();

    $lines = explode("\r\n", $this->get('/file/2026/3/sawt/export')->getContent());

    expect($lines[1])->toBe('DSAWT,D1701Q,1,008123456,0000,"ACME CO.","","","",09/2026,WI010,5.00,20000.00,1000.00');
});

test('the fourth quarter goes with the annual return', function () {
    $this->get('/file/2026/4/sawt')->assertInertia(fn (Assert $page) => $page
        ->where('form', '1701-MS')
        ->where('fileName', '1234567890000122026'.'1701-MS.DAT'));
});

test('without a TIN there is no file to export', function () {
    TaxProfile::current()->update(['tin' => null]);

    $this->get('/file/2026/3/sawt')->assertInertia(fn (Assert $page) => $page->where('fileName', null));
    $this->get('/file/2026/3/sawt/export')->assertNotFound();
});

test('a quarter that hasn’t started has no SAWT', function () {
    $this->get('/file/2027/1/sawt')->assertNotFound();
});
