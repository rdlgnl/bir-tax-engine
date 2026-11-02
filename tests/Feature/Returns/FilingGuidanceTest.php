<?php

use App\Profiles\Models\TaxProfile;
use App\Profiles\Models\TaxYear;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Enums\TaxpayerClassification;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-05 10:00'));
});

function annualForm(TaxProfile $profile): string
{
    return app(TaxCalculationService::class)->forYear($profile, 2026)['quarters'][4]['form'];
}

test('the 1701-MS is for micro and small taxpayers; medium ones file the 1701A or 1701', function () {
    $profile = TaxProfile::factory()->create();
    Transaction::factory()->on('2026-03-01')->create(['amount' => 500_000]);

    expect(annualForm($profile))->toBe('1701-MS');

    // The class the BIR assigned wins over the estimate from sales.
    TaxYear::query()->create(['year' => 2026, 'classification' => TaxpayerClassification::Medium]);
    expect(annualForm($profile))->toBe('1701A');

    $profile->update(['regime' => 'graduated', 'deduction_method' => DeductionMethod::Itemized]);
    expect(annualForm($profile))->toBe('1701');

    // Only the form itself: no other accepted form, and no 1701-MS.
    $this->get('/file/2026/4/1701')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('alternativeForm', null)->where('paperFiling', false));
    $this->get('/file/2026/4/1701-MS')->assertNotFound();
});

test('only the 1701-MS goes on paper; other returns are e-filed, their PDF a worksheet', function () {
    TaxProfile::factory()->create();
    Transaction::factory()->on('2026-03-01')->create(['amount' => 500_000]);

    $this->get('/file/2026/4/1701-MS')->assertInertia(fn (Assert $page) => $page
        ->component('returns/annual')->where('paperFiling', true)->where('alternativeForm', '1701A'));
    $this->get('/file/2026/3/1701Q')->assertInertia(fn (Assert $page) => $page->where('paperFiling', false));
    $this->get('/file/2026/3/1701Q/form')->assertInertia(fn (Assert $page) => $page->where('paperFiling', false));
});

test('financial statements follow the deduction method, audited above ₱3,000,000', function (string $regime, ?DeductionMethod $method, float $sales, string $expected) {
    TaxProfile::factory()->create(['regime' => $regime, 'deduction_method' => $method]);
    Transaction::factory()->on('2026-03-01')->create(['amount' => $sales]);

    $this->get('/file/2026/4/'.annualForm(TaxProfile::current()))->assertInertia(fn (Assert $page) => $page
        ->component('returns/annual')->where('financialStatements', $expected));
})->with([
    '8%' => ['eight_percent', null, 900_000, 'none'],
    'graduated, 40% deduction' => ['graduated', DeductionMethod::Osd, 2_000_000, 'none'],
    'graduated, itemized' => ['graduated', DeductionMethod::Itemized, 2_000_000, 'unaudited'],
    'graduated, itemized, exactly ₱3M' => ['graduated', DeductionMethod::Itemized, 3_000_000, 'unaudited'],
    'graduated, itemized, over ₱3M' => ['graduated', DeductionMethod::Itemized, 3_500_000, 'audited'],
]);
