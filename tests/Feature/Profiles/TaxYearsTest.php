<?php

use App\Data\Services\LocalData;
use App\Profiles\Enums\IncomeSource;
use App\Profiles\Models\TaxProfile;
use App\Profiles\Models\TaxYear;
use App\Returns\Models\Filing;
use App\Returns\Services\Amendments;
use App\Returns\Services\ReturnForm;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Enums\Regime;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
});

test('switching method in a new year leaves last year on the method it was filed on', function () {
    // January 2027: Q1 2027 isn't filed, so the method can change for 2027.
    $this->travelTo(CarbonImmutable::parse('2027-01-20 10:00'));
    $engine = app(TaxCalculationService::class);
    $before = $engine->forYear(TaxProfile::current(), 2026);
    $amendmentsBefore = app(Amendments::class)->needed(TaxProfile::current());

    TaxProfile::current()->update(['regime' => Regime::Graduated, 'deduction_method' => DeductionMethod::Osd]);

    expect(TaxYear::query()->where('year', 2026)->sole())
        ->regime->toBe(Regime::EightPercent)
        ->deduction_method->toBeNull()
        ->vat_registered->toBeFalse()
        ->and(TaxYear::query()->where('year', 2027)->exists())->toBeFalse();

    $profile = TaxProfile::current();
    expect($engine->forYear($profile, 2026)['quarters'])->toBe($before['quarters'])
        ->and($engine->forYear($profile, 2026)['regime'])->toBe('eight_percent')
        ->and($engine->forYear($profile, 2027)['regime'])->toBe('graduated')
        ->and(app(Amendments::class)->needed($profile))->toBe($amendmentsBefore);
});

test('a registration change applies from this year, and the years before keep theirs', function () {
    $this->travelTo(CarbonImmutable::parse('2027-03-01 10:00'));
    TaxProfile::current()->update(['vat_registered' => true, 'regime' => Regime::Graduated, 'deduction_method' => DeductionMethod::Itemized]);

    expect(TaxProfile::current()->forYear(2026))->vat_registered->toBeFalse()->regime->toBe(Regime::EightPercent)
        ->and(TaxProfile::current()->forYear(2027))->vat_registered->toBeTrue()->regime->toBe(Regime::Graduated);

    // The 2026 SLSP is for VAT-registered years only.
    $this->get('/file/2026/3/slsp')->assertNotFound();
});

test('the method elected on an earlier year’s Q1 return wins over the profile', function () {
    TaxYear::query()->create(['year' => 2025, 'regime' => Regime::Graduated, 'deduction_method' => DeductionMethod::Osd]);

    // A blank field keeps the profile's current value.
    expect(TaxProfile::current()->forYear(2025))
        ->regime->toBe(Regime::Graduated)
        ->deduction_method->toBe(DeductionMethod::Osd)
        ->vat_registered->toBe(TaxProfile::current()->vat_registered)
        ->and(TaxProfile::current()->forYear(2026)->regime)->toBe(Regime::EightPercent);
});

test('a profile copied for one year can’t be saved', function () {
    TaxYear::query()->create(['year' => 2025, 'regime' => Regime::Graduated]);

    expect(fn () => TaxProfile::current()->forYear(2025)->save())->toThrow(LogicException::class);
});

test('setting up for the first time doesn’t freeze anything', function () {
    // The seeded Q1 2026 return already recorded 2026.
    $kept = TaxYear::query()->count();
    TaxProfile::current()->forceFill(['regime' => null])->saveQuietly();
    TaxProfile::current()->update(['regime' => Regime::EightPercent]);

    expect(TaxYear::query()->count())->toBe($kept);
});

test('the years’ choices are kept in backups', function () {
    TaxYear::query()->create(['year' => 2025, 'regime' => Regime::Graduated]);

    expect(app(LocalData::class)->rows()['tax_years'])->toHaveCount(TaxYear::query()->count())
        ->and(collect(app(LocalData::class)->rows()['tax_years'])->pluck('year'))->toContain(2025, 2026);
});

test('a filed 2551Q cites the percentage tax rule of its quarter', function () {
    TaxProfile::current()->update(['regime' => Regime::Graduated, 'deduction_method' => DeductionMethod::Osd, 'income_source' => IncomeSource::Business]);
    Transaction::factory()->on('2022-02-10')->create(['counterparty' => 'Old Client', 'amount' => 100_000]);

    expect(app(ReturnForm::class)->snapshot(TaxProfile::current(), 2022, 1, '2551Q')['rules'])->toBe(['PT-1PCT v1'])
        ->and(app(ReturnForm::class)->snapshot(TaxProfile::current(), 2022, 1, '1701Q')['rules'])->toContain('IT-GRAD-2018 v1');
});

test('filing a Q1 income tax return records the year’s method', function () {
    $this->travelTo(CarbonImmutable::parse('2027-05-10 10:00'));
    Filing::factory()->quarter(1, '2027-05-10', 0)->create(['year' => 2027, 'snapshot' => ['regime' => 'graduated', 'deduction_method' => 'osd']]);

    expect(TaxYear::query()->where('year', 2027)->sole())
        ->regime->toBe(Regime::Graduated)
        ->deduction_method->toBe(DeductionMethod::Osd);
});
