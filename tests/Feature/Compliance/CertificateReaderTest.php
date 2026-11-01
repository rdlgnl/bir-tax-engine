<?php

use App\Compliance\Services\CertificateReader;
use App\Returns\Forms\OfficialFormFiller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
});

/**
 * A client's 2307 on the BIR's own form (design 3-02): Lumen Digital's
 * Q3 certificate to the demo taxpayer, as eBIRForms or the app fill it.
 */
function lumenCertificate(): string
{
    $months = [20_000, 0, 0];

    return app(OfficialFormFiller::class)->render('2307', [
        '1.from' => '07012026', '1.to' => '09302026',
        'payee.tin.1' => '123', 'payee.tin.2' => '456', 'payee.tin.3' => '789',
        'payee.name' => 'DELA CRUZ, JUAN SANTOS',
        'payor.tin.1' => '008', 'payor.tin.2' => '123', 'payor.tin.3' => '456',
        'payor.name' => 'LUMEN DIGITAL INC.',
        'payor.address' => '5F TECH HUB, ORTIGAS CENTER, PASIG CITY', 'payor.zip' => '1605',
        'row1.description' => 'Professional fees', 'row1.atc' => 'WI010',
        'row1.month1' => $months[0], 'row1.month2' => $months[1], 'row1.month3' => $months[2], 'row1.total' => 20_000, 'row1.withheld' => 1_000,
        'total.month1' => $months[0], 'total.month2' => $months[1], 'total.month3' => $months[2], 'total.total' => 20_000, 'total.withheld' => 1_000,
    ]);
}

function readPdf(string $pdf): array
{
    $path = tempnam(sys_get_temp_dir(), '2307');
    file_put_contents($path, $pdf);

    try {
        return app(CertificateReader::class)->read($path, 'application/pdf');
    } finally {
        unlink($path);
    }
}

test('a 2307 on the BIR’s form is read field by field, and sure of each (design 3-02)', function () {
    $reading = readPdf(lumenCertificate());

    expect($reading['readable'])->toBeTrue()
        ->and(array_map(fn (array $field) => $field['value'], $reading['fields']))->toBe([
            'payor_name' => 'Lumen Digital Inc.',
            'payor_tin' => '008-123-456',
            'year' => 2026,
            'quarter' => 3,
            'amount_paid' => 20000.0,
            'tax_withheld' => 1000.0,
            'atc_code' => 'WI010',
        ])
        ->and(collect($reading['fields'])->every(fn (array $field) => $field['sure']))->toBeTrue();
});

test('a 2307 another program laid out is read too, asking to check what it can’t confirm', function () {
    $pdf = Pdf::loadHTML(<<<'HTML'
        <h1>Certificate of Creditable Tax Withheld at Source</h1>
        <p>For the period July 1, 2026 to September 30, 2026</p>
        <p>Payee TIN: 123-456-789-000</p>
        <p>Payee: DELA CRUZ, JUAN SANTOS</p>
        <p>TIN: 009-876-543-000</p>
        <p>Payor's Name: NORTHSTAR LOGISTICS CORP.</p>
        <table>
            <tr><td>Professional fees</td><td>WI010</td><td>15,000.00</td><td>15,000.00</td><td>0.00</td><td>30,000.00</td><td>3,000.00</td></tr>
            <tr><td>Total</td><td></td><td>15,000.00</td><td>15,000.00</td><td>0.00</td><td>30,000.00</td><td>3,000.00</td></tr>
        </table>
        HTML)->output();

    $fields = readPdf($pdf)['fields'];

    // A new client, and 10% where WI010 is 5%: both to check.
    expect($fields)->toMatchArray([
        'payor_name' => ['value' => 'Northstar Logistics Corp.', 'sure' => false],
        'payor_tin' => ['value' => '009-876-543-000', 'sure' => true],
        'year' => ['value' => 2026, 'sure' => true],
        'quarter' => ['value' => 3, 'sure' => true],
        'amount_paid' => ['value' => 30000.0, 'sure' => false],
        'tax_withheld' => ['value' => 3000.0, 'sure' => false],
        'atc_code' => ['value' => 'WI010', 'sure' => true],
    ]);
});

test('a photo or a scan without text isn’t readable, so it’s typed in', function () {
    $scan = Pdf::loadHTML('<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==">')->output();

    expect(readPdf($scan)['readable'])->toBeFalse()
        ->and(app(CertificateReader::class)->read(UploadedFile::fake()->image('2307.jpg')->getRealPath(), 'image/jpeg')['readable'])->toBeFalse();
});

test('reading a file on the 2307 page keeps nothing until it’s saved', function () {
    $file = UploadedFile::fake()->createWithContent('2307_Lumen_Q3.pdf', lumenCertificate());

    $this->post('/certificates/read', ['file' => $file])
        ->assertOk()
        ->assertJsonPath('readable', true)
        ->assertJsonPath('fields.payor_name.value', 'Lumen Digital Inc.')
        ->assertJsonPath('fields.tax_withheld.value', 1000);

    expect(Storage::disk('local')->allFiles())->toBe([]);

    $this->post('/certificates/read', [])->assertSessionHasErrors('file');
});
