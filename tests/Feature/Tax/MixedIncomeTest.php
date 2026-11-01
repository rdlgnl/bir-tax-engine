<?php

use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\OfficialForms;
use App\Returns\Services\ReturnForm;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Enums\TransactionType;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2027-02-10 10:00'));
});

/**
 * Liza from the design: a ₱50,000-a-month job at Northwind, and a side
 * business that earned ₱170,000 by September and ₱200,000 for the year.
 */
function sideBusiness(TaxProfile $profile): TaxProfile
{
    foreach (['2026-02-10' => 40_000, '2026-05-10' => 40_000, '2026-08-10' => 90_000, '2026-11-10' => 30_000] as $date => $amount) {
        Transaction::query()->create(['type' => TransactionType::Payment, 'occurred_on' => $date, 'counterparty' => 'Lumen Digital', 'amount' => $amount]);
    }

    return $profile;
}

function annualValues(TaxProfile $profile, string $form): array
{
    return app(OfficialForms::class)->values(app(ReturnForm::class)->snapshot($profile, 2026, 4, $form));
}

test('on 8%, the quarters tax only the business, with no ₱250,000 reduction', function () {
    $profile = sideBusiness(TaxProfile::factory()->withJob()->create());

    $q3 = app(TaxCalculationService::class)->forYear($profile, 2026)['quarters'][3];

    expect($q3)->toMatchArray([
        'gross' => 170000.0, 'exempt_amount' => 0.0, 'income_tax' => 13600.0,
        'prior_payments' => 6400.0, 'due' => 7200.0, 'compensation' => null,
    ]);
});

test('the annual return adds the salary and credits what the employer withheld', function () {
    $profile = sideBusiness(TaxProfile::factory()->withJob()->create());

    $q4 = app(TaxCalculationService::class)->forYear($profile, 2026)['quarters'][4];

    expect($q4)->toMatchArray(['income_tax' => 16000.0, 'prior_payments' => 13600.0, 'due' => 2400.0])
        ->and($q4['compensation'])->toBe([
            'employer' => 'Northwind BPO Inc.', 'employer_tin' => null, 'gross' => 600000.0, 'non_taxable' => 0.0,
            'taxable' => 600000.0, 'tax' => 62500.0, 'withheld' => 62500.0, 'source' => 'estimate',
        ]);
});

test('on graduated rates the business is taxed on top of the salary', function () {
    $profile = sideBusiness(TaxProfile::factory()->withJob()->graduated(DeductionMethod::Osd)->create());
    $quarters = app(TaxCalculationService::class)->forYear($profile, 2026)['quarters'];

    // On its own, ₱102,000 of business income is under the ₱250,000 threshold.
    expect($quarters[3])->toMatchArray(['taxable' => 102000.0, 'income_tax' => 0.0, 'due' => 0.0])
        // With the salary, ₱720,000 owes ₱86,500, of which ₱62,500 is the salary's.
        ->and($quarters[4])->toMatchArray(['taxable' => 120000.0, 'income_tax' => 24000.0, 'due' => 24000.0])
        ->and($quarters[4]['compensation'])->toMatchArray(['tax' => 62500.0, 'withheld' => 62500.0]);
});

test('without a salary recorded, the annual return is the business alone', function () {
    $profile = sideBusiness(TaxProfile::factory()->withSalary()->create());

    expect(app(TaxCalculationService::class)->forYear($profile, 2026)['quarters'][4])
        ->toMatchArray(['income_tax' => 16000.0, 'due' => 2400.0, 'compensation' => null]);
});

test('the 1701 lists the employer and totals the salary with the business', function () {
    $profile = sideBusiness(TaxProfile::factory()->withJob()->create());

    expect(annualValues($profile, '1701'))->toMatchArray([
        '6.compensation_earner' => true,
        'S1.1.taxpayer' => true, 'S1.1.employer' => 'NORTHWIND BPO INC.',
        'S1.1.compensation' => 600000, 'S1.1.withheld' => 62500, 'S1.3A.compensation' => 600000, 'S1.3A.withheld' => 62500,
        'S2.4' => 600000, 'S2.5' => 0, 'S2.6' => 600000, 'S2.7' => 62500,
        'S3B.26' => 200000, 'S3B.29' => 0, 'S3B.31' => 16000, 'S3B.32' => 78500,
        'VI.1' => 78500, 'VII.2' => 13600, 'VII.5' => 62500, 'VII.10' => 76100,
        '22A' => 78500, '23A' => 76100, '24A' => 2400,
    ]);
});

test('on graduated rates the 1701 taxes salary and business together', function () {
    $profile = sideBusiness(TaxProfile::factory()->withJob()->graduated(DeductionMethod::Osd)->create());

    expect(annualValues($profile, '1701'))->toMatchArray([
        'S2.6' => 600000, 'S3A.18' => 120000, 'S3A.24' => 720000, 'S3A.25' => 86500,
        'VI.1' => 86500, 'VII.5' => 62500, '24A' => 24000,
    ])->not->toHaveKey('S2.7');
});

test('the shorter 1701-MS is accepted too, with the salary in Part IV A', function () {
    $profile = sideBusiness(TaxProfile::factory()->withJob()->create());

    expect(annualValues($profile, '1701-MS'))->toMatchArray([
        '11.mixed' => true, 'iv.a1' => 600000, 'iv.a3' => 600000, 'iv.a4' => 62500,
        'iv.24' => 16000, 'iv.25' => 78500, 'v.5' => 62500, '22A' => 78500, '30' => 2400,
    ]);

    $this->get('/file/2026/4/1701')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('alternativeForm', '1701-MS'));
    $this->get('/file/2026/4/1701-MS/form')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('layout.with_salary', true)
        ->where('layout.lines', fn ($lines) => collect($lines)->contains(fn ($line) => $line['label'] === 'Less: tax withheld by your employer (2316)' && $line['amount'] == 62500)));
});

test('setup asks about the job only when there is one, and Home shows it', function () {
    $profile = TaxProfile::factory()->withSalary()->create();

    $this->put('/setup/job', ['employer_name' => 'Northwind BPO Inc.', 'monthly_salary' => '50000'])
        ->assertRedirect('/setup/about');

    expect($profile->fresh())->employer_name->toBe('Northwind BPO Inc.')->monthly_salary->toBe('50000.00');

    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));

    $this->get('/home')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('job.employer', 'Northwind BPO Inc.')
        ->where('job.annual_salary', 600000)
        ->where('job.employer_withholds', 62500)
        ->where('job.annual_form', '1701'));

    $this->put('/profile/job', ['employer_name' => 'Acme Corp', 'monthly_salary' => '60000'])->assertRedirect();
    expect($profile->fresh()->monthly_salary)->toBe('60000.00');

    $profile->update(['income_source' => 'business']);
    $this->put('/profile/job', ['employer_name' => 'Acme Corp', 'monthly_salary' => '60000'])->assertNotFound();
    $this->get('/home')->assertInertia(fn (Assert $page) => $page->where('job', null));
    $this->get('/setup/job')->assertRedirect();
});
