<?php

use App\Compliance\Models\CompensationCertificate;
use App\Compliance\Services\TaxObligations;
use App\Data\Services\LocalData;
use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\OfficialForms;
use App\Returns\Services\ReturnForm;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Enums\TransactionType;
use App\Transactions\Models\Transaction;
use App\Withholding\Models\IssuedCertificate;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->travelTo(CarbonImmutable::parse('2027-02-10 10:00'));
});

/** Liza from the design, whose 2316 shows a ₱12,000 taxable bonus above the estimate. */
function employee(): TaxProfile
{
    $profile = TaxProfile::factory()->withJob()->create();

    foreach (['2026-02-10' => 40_000, '2026-05-10' => 40_000, '2026-08-10' => 90_000, '2026-11-10' => 30_000] as $date => $amount) {
        Transaction::query()->create(['type' => TransactionType::Payment, 'occurred_on' => $date, 'counterparty' => 'Lumen Digital', 'amount' => $amount]);
    }

    return $profile;
}

function the2316(array $overrides = []): array
{
    return [
        'employer_name' => 'Northwind BPO Inc.', 'employer_tin' => '008-111-222-000',
        'gross_compensation' => '702000', 'non_taxable_compensation' => '90000', 'tax_withheld' => '64900',
        ...$overrides,
    ];
}

test('the page compares the estimate with the 2316 for last year, early in the year', function () {
    employee();

    $this->get('/2316')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('compliance/compensation')
        ->where('year', 2026)
        ->where('certificate', null)
        ->where('estimate', ['taxable' => 600000, 'withheld' => 62500, 'tax' => 62500])
        ->where('actual', null));

    $this->post('/2316/2026', the2316())->assertSessionHasNoErrors()->assertRedirect();

    $this->get('/2316/2026')->assertInertia(fn (Assert $page) => $page
        ->where('certificate.employer_tin', '008-111-222-000')
        ->where('actual', ['taxable' => 612000, 'withheld' => 64900, 'tax' => 64900]));
});

test('the 2316 is checked, and its photo kept on this computer and replaced on update', function () {
    employee();

    $this->post('/2316/2026', the2316(['tax_withheld' => '800000']))->assertSessionHasErrors('tax_withheld');
    $this->post('/2316/2026', the2316(['non_taxable_compensation' => '900000']))->assertSessionHasErrors('non_taxable_compensation');
    $this->post('/2316/2026', the2316(['employer_tin' => '12-34']))->assertSessionHasErrors('employer_tin');

    $this->post('/2316/2026', [...the2316(), 'file' => UploadedFile::fake()->image('2316.jpg')])->assertSessionHasNoErrors();
    $first = CompensationCertificate::query()->sole()->file_path;
    Storage::disk('local')->assertExists($first);
    expect($first)->toStartWith('certificates/2316/');

    $this->post('/2316/2026', [...the2316(), 'file' => UploadedFile::fake()->create('2316.pdf', 50, 'application/pdf')])->assertSessionHasNoErrors();
    $certificate = CompensationCertificate::query()->sole();
    Storage::disk('local')->assertMissing($first);
    expect($certificate->original_filename)->toBe('2316.pdf');
    $this->get('/2316/2026/file')->assertOk();
});

test('the annual return uses the 2316 instead of the estimate', function () {
    $profile = employee();
    $this->post('/2316/2026', the2316(['tax_withheld' => '60000']));

    $q4 = app(TaxCalculationService::class)->forYear($profile, 2026)['quarters'][4];

    // ₱4,900 short on the salary joins the ₱2,400 still owed on the business.
    expect($q4['compensation'])->toMatchArray([
        'employer_tin' => '008-111-222-000', 'gross' => 702000.0, 'non_taxable' => 90000.0,
        'taxable' => 612000.0, 'tax' => 64900.0, 'withheld' => 60000.0, 'source' => '2316',
    ])->and($q4['due'])->toBe(7300.0);

    expect(app(OfficialForms::class)->values(app(ReturnForm::class)->snapshot($profile, 2026, 4, '1701')))->toMatchArray([
        'S1.1.tin' => '00811122200000', 'S1.1.compensation' => 702000, 'S1.1.withheld' => 60000,
        'S2.4' => 702000, 'S2.5' => 90000, 'S2.6' => 612000, 'S2.7' => 64900, 'VII.5' => 60000, '24A' => 7300,
    ]);
});

test('the calendar reminds about the 2316 until it is in', function () {
    $profile = employee();
    $reminders = function () use ($profile) {
        $year = app(TaxCalculationService::class)->forYear($profile, 2026);

        return array_values(array_filter(
            app(TaxObligations::class)->deadlines($profile, $year),
            fn (array $item) => str_contains($item['title'], '2316'),
        ));
    };

    expect($reminders())->toHaveCount(1)
        ->and($reminders()[0])->toMatchArray([
            'date' => '2027-01-31', 'title' => 'Get your 2316 from Northwind BPO Inc.', 'kind' => 'reminder', 'href' => '/2316/2026',
        ]);

    $this->post('/2316/2026', the2316());

    expect($reminders())->toBe([]);
});

test('only someone with a job and a business has a 2316 page', function () {
    TaxProfile::factory()->create();

    $this->get('/2316')->assertNotFound();
    $this->post('/2316/2026', the2316())->assertNotFound();
});

test('backups and erasing include 2316s and the 2307s given to payees', function () {
    employee();
    $this->post('/2316/2026', the2316());
    IssuedCertificate::query()->create(['year' => 2026, 'quarter' => 3, 'payee' => 'Leo Bautista', 'given_on' => '2026-10-20']);

    $rows = app(LocalData::class)->rows();
    expect($rows['compensation_certificates'])->toHaveCount(1)
        ->and($rows['issued_certificates'])->toHaveCount(1);

    app(LocalData::class)->erase();
    expect(CompensationCertificate::query()->count())->toBe(0)
        ->and(IssuedCertificate::query()->count())->toBe(0);

    app(LocalData::class)->replace($rows);
    expect(CompensationCertificate::query()->sole()->employer_name)->toBe('Northwind BPO Inc.')
        ->and(IssuedCertificate::query()->sole()->payee)->toBe('Leo Bautista');
});
