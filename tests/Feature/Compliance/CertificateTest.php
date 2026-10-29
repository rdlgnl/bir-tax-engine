<?php

use App\Compliance\Models\Certificate;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
    Storage::fake('local');
});

function acmeCertificate(array $overrides = []): array
{
    return [
        'payor_name' => 'Acme Co.', 'payor_tin' => '008-123-456-000', 'year' => 2026, 'quarter' => 3,
        'amount_paid' => '20000', 'tax_withheld' => '1000', 'atc_code' => 'WI010',
        ...$overrides,
    ];
}

test('the add page can start from a payment', function () {
    $acme = Transaction::query()->where('counterparty', 'Acme Co.')->whereDate('occurred_on', '2026-08-19')->first();

    $this->get("/certificates/create?transaction={$acme->id}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('certificates/create')
        ->where('prefill', ['payor_name' => 'Acme Co.', 'year' => 2026, 'quarter' => 3, 'amount_paid' => 20000, 'tax_withheld' => 1000])
        ->where('clients', ['Acme Co.', 'Lumen Digital'])
        ->where('quarters.0', ['year' => 2026, 'quarter' => 4]));
});

test('the match finds the payments a 2307 covers, whatever the company suffix', function (string $name) {
    $this->postJson('/certificates/match', ['payor_name' => $name, 'year' => 2026, 'quarter' => 3, 'tax_withheld' => 1000])
        ->assertOk()
        ->assertJsonPath('status', 'exact')
        ->assertJsonPath('payments.0.occurred_on', '2026-08-19')
        ->assertJsonCount(1, 'payments');
})->with(['Acme Co.', 'ACME CO', 'Acme Corporation', 'acme co., inc.']);

test('the match reports a wrong amount or an unknown client', function () {
    $this->postJson('/certificates/match', acmeCertificate(['tax_withheld' => 900]))->assertJsonPath('status', 'partial');
    $this->postJson('/certificates/match', acmeCertificate(['payor_name' => 'Nobody Ltd']))->assertJsonPath('status', 'none');
    $this->postJson('/certificates/match', acmeCertificate(['payor_name' => 'Acme Ventures']))->assertJsonPath('status', 'none');
});

test('saving a matching 2307 confirms the credit and keeps the file', function () {
    $file = UploadedFile::fake()->create('2307_Acme_Q3.pdf', 120, 'application/pdf');

    $this->post('/certificates', [...acmeCertificate(), 'file' => $file])
        ->assertRedirect(route('compliance.show', 3))
        ->assertSessionHasNoErrors();

    $certificate = Certificate::query()->sole();
    $acme = Transaction::query()->where('counterparty', 'Acme Co.')->whereDate('occurred_on', '2026-08-19')->first();

    expect($acme->certificate_id)->toBe($certificate->id)
        ->and($acme->certificate_received_on->toDateString())->toBe('2026-10-27')
        ->and($certificate->original_filename)->toBe('2307_Acme_Q3.pdf');
    Storage::disk('local')->assertExists($certificate->file_path);

    $this->get("/certificates/{$certificate->id}/file")->assertOk();
    $this->get('/compliance')->assertInertia(fn (Assert $page) => $page
        ->where('certificates.confirmed', 10000)
        ->where('checklist.items.2.done', true));
});

test('a 2307 that doesn’t add up is kept but confirms nothing', function () {
    $this->post('/certificates', acmeCertificate(['tax_withheld' => '900']))->assertSessionHasNoErrors();

    expect(Certificate::count())->toBe(1)
        ->and(Transaction::query()->whereNotNull('certificate_id')->count())->toBe(0);
});

test('certificate details are validated', function (array $data, string $field) {
    $this->post('/certificates', acmeCertificate($data))->assertSessionHasErrors($field);
})->with([
    'no client' => [['payor_name' => ''], 'payor_name'],
    'withheld more than paid' => [['tax_withheld' => '30000'], 'tax_withheld'],
    'bad TIN' => [['payor_tin' => '12-3'], 'payor_tin'],
    'not a PDF or photo' => [['file' => UploadedFile::fake()->create('notes.docx', 10)], 'file'],
    'too large' => [['file' => UploadedFile::fake()->create('big.pdf', 11000, 'application/pdf')], 'file'],
]);

test('a certificate without a file has nothing to show', function () {
    $this->post('/certificates', acmeCertificate());

    $this->get('/certificates/'.Certificate::query()->sole()->id.'/file')->assertNotFound();
});
