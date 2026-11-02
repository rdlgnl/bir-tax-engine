<?php

use App\Corporations\Models\Corporation;
use App\Corporations\Services\CorporateTax;
use App\Payroll\Services\Payroll;
use App\Profiles\Enums\IncomeSource;
use App\Profiles\Models\TaxProfile;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Enums\Regime;
use App\Tax\Services\NoTaxRules;
use App\Tax\Services\TaxCalculationService;
use App\Tax\Services\TaxExplainer;
use App\Tax\Services\TaxRules;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
    $this->rules = app(TaxRules::class);
});

/**
 * A purely self-employed taxpayer on graduated rates with the 40% OSD, and
 * ₱250,000 of sales in each quarter of a year.
 */
function graduatedYear(int $year): array
{
    $profile = TaxProfile::current();
    $profile->update(['regime' => Regime::Graduated, 'deduction_method' => DeductionMethod::Osd, 'income_source' => IncomeSource::Business]);

    foreach ([2, 5, 8, 11] as $month) {
        Transaction::factory()->on(sprintf('%d-%02d-10', $year, $month))->create(['counterparty' => 'Old Client', 'amount' => 250_000]);
    }

    return app(TaxCalculationService::class)->forYear($profile->refresh(), $year);
}

test('each date has the rules in force on it, and none before 2018', function () {
    $on = fn (string $date) => $this->rules->on(CarbonImmutable::parse($date));

    expect($on('2020-06-30')->rate('percentage_tax_rate'))->toBe(0.03)
        ->and($on('2020-07-01')->rate('percentage_tax_rate'))->toBe(0.01)
        ->and($on('2023-06-30')->rate('percentage_tax_rate'))->toBe(0.01)
        ->and($on('2023-07-01')->rate('percentage_tax_rate'))->toBe(0.03)
        ->and($on('2022-12-31')->brackets()[1])->toBe([250_000, 0, 0.20])
        ->and($on('2023-01-01')->brackets()[1])->toBe([250_000, 0, 0.15])
        ->and($on('2020-12-31')->has('corporation.rate'))->toBeFalse()
        ->and($on('2021-01-01')->rate('corporation.mcit_rate'))->toBe(0.01)
        ->and($on('2023-07-01')->rate('corporation.mcit_rate'))->toBe(0.02);

    expect(fn () => $on('2017-12-31'))->toThrow(NoTaxRules::class, "BuwisBuddy's tax rules start on January 1, 2018.");
});

test('a 2022 return uses the 2018–2022 table and 1% percentage tax', function () {
    $year = graduatedYear(2022);

    // ₱1M sales, 40% OSD: ₱600,000 taxable. 2018 table: ₱30,000 + 25% of ₱200,000.
    expect($year['quarters'][4])->toMatchArray(['taxable' => 600_000.0, 'income_tax' => 80_000.0, 'percentage_tax' => 2_500.0])
        ->and($year['quarters'][1]['percentage_tax'])->toBe(2_500.0);
});

test('2023 uses the new table, with 1% percentage tax until June and 3% after', function () {
    $year = graduatedYear(2023);

    // The same ₱600,000 taxable on the 2023 table: ₱22,500 + 20% of ₱200,000.
    expect($year['quarters'][4]['income_tax'])->toBe(62_500.0)
        ->and(array_map(fn (int $q) => $year['quarters'][$q]['percentage_tax'], [1, 2, 3, 4]))->toBe([2_500.0, 2_500.0, 7_500.0, 7_500.0]);
});

test('the explanation cites the rules the year was computed on', function () {
    $year = graduatedYear(2022);
    $profile = TaxProfile::current();
    $ids = fn (int $y, array $quarter) => array_column(app(TaxExplainer::class)->explain($profile, $y, $quarter, Transaction::query()->forYear($y)->get(), CarbonImmutable::now())['rules'], 'id');

    expect($ids(2022, $year['quarters'][2]))->toContain('IT-GRAD-2018', 'PT-1PCT')
        ->and($ids(2026, app(TaxCalculationService::class)->forYear($profile, 2026)['quarters'][3]))->toContain('IT-GRAD-2023', 'PT-3PCT');
});

test('years before 2018 are refused where records come in and where returns are shown', function () {
    $this->post('/transactions', ['type' => 'payment', 'occurred_on' => '2017-12-31', 'counterparty' => 'Old Client', 'amount' => 1_000])
        ->assertSessionHasErrors(['occurred_on' => "BuwisBuddy's tax rules start in 2018. Ask your accountant about earlier years."]);

    $this->get('/file/2017/1/1701Q')->assertNotFound();
});

test('payroll withholding uses the year’s table', function () {
    // ₱30,000 a month: Annex "D" to 2022, 20% over ₱20,833; Annex "E" from 2023, 15%.
    expect(app(Payroll::class)->monthlyWithholding(30_000, 2022))->toBe(1833.4)
        ->and(app(Payroll::class)->monthlyWithholding(30_000, 2026))->toBe(1375.05);
});

test('a corporation’s MCIT takes each quarter’s rate, and years before 2021 aren’t computed', function () {
    $corporation = Corporation::query()->create(['name' => 'Bayani Creative Studio Inc.', 'operations_began' => 2018, 'total_assets' => 30_000_000, 'deduction_method' => 'itemized']);

    foreach ([1, 2, 3, 4] as $quarter) {
        $corporation->quarters()->create(['year' => 2023, 'quarter' => $quarter, 'sales' => 1_500_000, 'cost_of_sales' => 500_000, 'other_income' => 0, 'deductions' => 990_000, 'withheld' => 0]);
    }

    $tax = app(CorporateTax::class);

    // ₱1M gross income a quarter: 1% for Q1 and Q2, 2% for Q3 and Q4.
    expect($tax->quarter($corporation, 2023, 2)['mcit'])->toBe(20_000.0)
        ->and($tax->quarter($corporation, 2023, 3)['mcit'])->toBe(40_000.0)
        ->and($tax->annual($corporation, 2023)['mcit'])->toBe(60_000.0)
        ->and($tax->covers(2020))->toBeFalse()
        ->and($tax->covers(2021))->toBeTrue();

    $this->put('/corporation/2020/1', ['sales' => 1, 'cost_of_sales' => 0, 'other_income' => 0, 'deductions' => 0, 'withheld' => 0])->assertNotFound();
});
