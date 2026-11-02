<?php

use App\Compliance\Services\TaxObligations;
use App\Profiles\Models\TaxProfile;
use App\Profiles\Models\TaxYear;
use App\Tax\Enums\TaxpayerClassification;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-05 10:00'));
    $this->profile = TaxProfile::factory()->create();
});

function noticeTitles(TaxProfile $profile): array
{
    $deadlines = app(TaxObligations::class)->deadlines($profile, app(TaxCalculationService::class)->forYear($profile, 2026));

    return collect($deadlines)->where('kind', 'reminder')->keyBy('title')->all();
}

test('a micro taxpayer with a 2025 return unfiled is pointed to the RR 4-2026 abatement', function () {
    // Q1 2025's 1701Q was due May 15, 2025 and never filed.
    Transaction::factory()->on('2025-02-10')->create(['amount' => 500_000]);

    $this->get('/file/2025/1/1701Q')->assertInertia(fn (Assert $page) => $page
        ->where('abatement', ['until' => '2026-12-31', 'fee' => 5000, 'ceiling' => 80000, 'source' => 'RR 4-2026']));
    $this->get('/file/2025/1/1701Q/late')->assertInertia(fn (Assert $page) => $page->where('abatement.fee', 5000));

    expect(noticeTitles($this->profile)['Clear back taxes for a ₱5,000 fee · micro taxpayers'] ?? null)
        ->toMatchArray(['date' => '2026-12-31', 'href' => '/file/2025/1/1701Q']);
});

test('the abatement isn’t offered for 2026 returns, to small taxpayers, or after December 31, 2026', function () {
    Transaction::factory()->on('2025-02-10')->create(['amount' => 500_000]);
    Transaction::factory()->on('2026-02-10')->create(['amount' => 500_000]);

    $this->get('/file/2026/1/1701Q')->assertInertia(fn (Assert $page) => $page->where('abatement', null));

    TaxYear::query()->create(['year' => 2025, 'classification' => TaxpayerClassification::Small]);
    $this->get('/file/2025/1/1701Q')->assertInertia(fn (Assert $page) => $page->where('abatement', null));
    expect(noticeTitles($this->profile))->not->toHaveKey('Clear back taxes for a ₱5,000 fee · micro taxpayers');

    TaxYear::query()->where('year', 2025)->update(['classification' => null]);
    $this->travelTo(CarbonImmutable::parse('2027-01-04 10:00'));
    $this->get('/file/2025/1/1701Q')->assertInertia(fn (Assert $page) => $page->where('abatement', null));
});

test('an online seller that isn’t micro is reminded to start e-invoicing by December 31, 2026', function () {
    Transaction::factory()->on('2026-03-01')->create(['counterparty' => 'Shopee', 'amount' => 400_000]);

    expect(noticeTitles($this->profile))->not->toHaveKey("Start e-invoicing through the BIR's EIS · online sellers");

    TaxYear::query()->create(['year' => 2026, 'classification' => TaxpayerClassification::Small]);

    expect(noticeTitles($this->profile)["Start e-invoicing through the BIR's EIS · online sellers"] ?? null)
        ->toMatchArray(['date' => '2026-12-31']);
});
