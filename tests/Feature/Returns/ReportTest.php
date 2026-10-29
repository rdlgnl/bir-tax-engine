<?php

use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
    Storage::fake('local');
});

/** @return list<list<string>> */
function csvRows(string $csv): array
{
    expect(str_starts_with($csv, "\u{FEFF}"))->toBeTrue();

    return array_map(
        fn (string $line) => str_getcsv($line, escape: ''),
        array_values(array_filter(explode("\n", substr($csv, 3)))),
    );
}

test('history offers the year’s reports once the page has loaded', function () {
    $this->get('/history')->assertInertia(fn (Assert $page) => $page
        ->missing('reports')
        ->loadDeferredProps(fn (Assert $reload) => $reload
            ->where('reports', [['year' => 2026, 'sawt_quarters' => [1, 2, 3, 4]]])));
});

test('the tax summary downloads as a PDF', function () {
    $response = $this->get('/reports/2026/tax-summary')->assertOk();

    expect($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($response->headers->get('Content-Disposition'))->toContain('tax-summary-2026.pdf')
        ->and(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

test('the income export lists every entry and opens in Excel', function () {
    $response = $this->get('/reports/2026/income')->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/csv')
        ->and($response->headers->get('Content-Disposition'))->toContain('income-and-withholding-2026.csv');

    $rows = csvRows($response->getContent());

    expect($rows[0])->toBe([
        'Date', 'Quarter', 'Type', 'Client / payee', 'Invoice no.', 'Category / description',
        'Amount', 'Tax withheld', '2307 status', '2307 received on', 'Valid invoice (expenses)',
    ])
        ->and($rows)->toHaveCount(19)
        ->and($rows[1])->toBe(['2026-01-12', 'Q1', 'Payment received', 'Studio Uno', '', '', '60000', '0', 'not_needed', '', ''])
        ->and($rows[16][2])->toBe('Expense')
        ->and($rows[16][10])->toBe('yes');
});

test('a client name can’t run as a spreadsheet formula', function () {
    Transaction::factory()->on('2026-10-01')->create(['counterparty' => '=HYPERLINK("http://evil.test","Click")']);

    $csv = $this->get('/reports/2026/income')->getContent();

    expect($csv)->toContain('"\'=HYPERLINK(""http://evil.test"",""Click"")"');
});

test('SAWT data lists each client who withheld tax in the quarter', function () {
    $rows = csvRows($this->get('/reports/2026/sawt/3')->assertOk()->getContent());

    expect($rows[0])->toBe(['Seq no.', 'Payor TIN', 'Payor name', 'ATC', 'Income payment', 'Tax rate (%)', 'Tax withheld', '2307'])
        ->and($rows)->toHaveCount(3)
        ->and($rows[1])->toBe(['1', '', 'Acme Co.', '', '20000', '5', '1000', '2307 not on file'])
        ->and($rows[2][2])->toBe('Lumen Digital');
});

test('SAWT uses the payor TIN and tax code from the client’s 2307', function () {
    $this->post('/certificates', [
        'payor_name' => 'Acme Co.', 'payor_tin' => '008-123-456-000', 'year' => 2026, 'quarter' => 3,
        'amount_paid' => '20000', 'tax_withheld' => '1000', 'atc_code' => 'WI010',
    ])->assertSessionHasNoErrors();

    $rows = csvRows($this->get('/reports/2026/sawt/3')->getContent());

    expect($rows[1])->toBe(['1', '008-123-456-000', 'Acme Co.', 'WI010', '20000', '5', '1000', '2307 on file']);
});

test('the accountant package is an encrypted ZIP of everything', function () {
    $response = $this->post('/reports/2026/package', ['password' => 'correct horse', 'password_confirmation' => 'correct horse'])
        ->assertOk()
        ->assertDownload('buwisbuddy-2026-for-accountant.zip');

    $zip = new ZipArchive;
    $zip->open($response->baseResponse->getFile()->getPathname());
    $names = array_map(fn (int $i) => $zip->getNameIndex($i), range(0, $zip->numFiles - 1));

    expect($names)->toEqualCanonicalizing([
        'README.txt',
        'tax-summary-2026.pdf',
        'income-and-withholding-2026.csv',
        'sawt/sawt-2026-Q1.csv',
        'sawt/sawt-2026-Q2.csv',
        'sawt/sawt-2026-Q3.csv',
        'sawt/sawt-2026-Q4.csv',
        'returns/1701Q-2026-Q1.pdf',
        'returns/1701Q-2026-Q2.pdf',
    ])
        ->and($zip->statName('README.txt')['encryption_method'])->toBe(ZipArchive::EM_AES_256)
        ->and($zip->getFromName('README.txt'))->toBeFalse();

    $zip->setPassword('correct horse');
    expect($zip->getFromName('README.txt'))->toContain('Tax records for 2026: Juan Dela Cruz');
    $zip->close();
});

test('the package needs a confirmed password of 8 characters', function (array $data) {
    $this->post('/reports/2026/package', $data)->assertSessionHasErrors('password');
})->with([
    'none' => [[]],
    'too short' => [['password' => 'short', 'password_confirmation' => 'short']],
    'mismatch' => [['password' => 'correct horse', 'password_confirmation' => 'correct horsf']],
]);

test('reports for a future year don’t exist', function () {
    $this->get('/reports/2027/tax-summary')->assertNotFound();
});
