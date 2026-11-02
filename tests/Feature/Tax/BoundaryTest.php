<?php

use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\OfficialForms;
use App\Returns\Services\ReturnForm;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-05 10:00'));
    $this->engine = app(TaxCalculationService::class);
});

test('exactly ₱250,000 on 8% owes no income tax', function () {
    $profile = TaxProfile::factory()->create();
    Transaction::factory()->on('2026-02-10')->create(['amount' => 250_000]);

    expect($this->engine->forYear($profile, 2026)['quarters'][1])->toMatchArray(['taxable' => 0.0, 'income_tax' => 0.0, 'due' => 0.0]);
});

test('exactly ₱3,000,000 on 8% stays on 8%', function () {
    $profile = TaxProfile::factory()->create();
    Transaction::factory()->on('2026-02-10')->create(['amount' => 3_000_000]);

    expect($this->engine->forYear($profile, 2026))->toMatchArray(['switched' => null])
        ->and($this->engine->forYear($profile, 2026)['quarters'][1])->toMatchArray(['regime' => 'eight_percent', 'income_tax' => 220000.0]);
});

test('each band of the graduated table starts at its base tax', function (int $taxable, float $tax) {
    expect($this->engine->graduatedTax($taxable, 2026))->toBe($tax);
})->with([
    [250_000, 0.0],
    [400_000, 22_500.0],
    [800_000, 102_500.0],
    [2_000_000, 402_500.0],
    [8_000_000, 2_202_500.0],
]);

test('expenses with no income never make the tax negative', function () {
    $profile = TaxProfile::factory()->graduated(DeductionMethod::Itemized)->create();
    Transaction::factory()->expense(validInvoice: true)->on('2026-02-10')->create(['amount' => 100_000]);

    expect($this->engine->forYear($profile, 2026)['quarters'][4])->toMatchArray(['taxable' => 0.0, 'income_tax' => 0.0, 'due' => 0.0]);
});

test('centavos round half up, and the whole-peso form stays within ₱1 of the engine', function () {
    $profile = TaxProfile::factory()->create();
    // 8% of ₱10,006.31 is ₱800.5048: ₱800.50 in the engine, ₱801 on the form.
    Transaction::factory()->on('2026-02-10')->create(['amount' => 260_006.31]);

    $q1 = $this->engine->forYear($profile, 2026)['quarters'][1];
    $form = app(OfficialForms::class)->values(app(ReturnForm::class)->snapshot($profile, 2026, 1, '1701Q'));

    expect($q1['income_tax'])->toBe(800.5)
        ->and($form['54A'])->toBe(801)
        ->and(abs($form['28A'] - $q1['due']))->toBeLessThan(1.0);
});
