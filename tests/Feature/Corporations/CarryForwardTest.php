<?php

use App\Compliance\Services\TaxObligations;
use App\Corporations\Models\Corporation;
use App\Corporations\Services\CorporateTax;
use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\Items\Form1702RTItems;
use App\Tax\Services\TaxCalculationService;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-05 10:00'));
    $this->corporation = Corporation::query()->create([
        'name' => 'Bayani Creative Studio Inc.', 'operations_began' => 2018, 'total_assets' => 30_000_000, 'deduction_method' => 'itemized',
    ]);
});

function corporateYear(Corporation $corporation, int $year, float $sales, float $deductions): void
{
    foreach ([1, 2, 3, 4] as $quarter) {
        $corporation->quarters()->create(['year' => $year, 'quarter' => $quarter, 'sales' => $sales, 'cost_of_sales' => 0, 'other_income' => 0, 'deductions' => $deductions, 'withheld' => 0]);
    }
}

test('a loss and an MCIT over the regular tax carry into the next year’s 1702-RT', function () {
    // 2024: a ₱1,200,000 loss, and ₱8,000 of MCIT (2% of ₱400,000) with no regular tax.
    corporateYear($this->corporation, 2024, 100_000, 400_000);
    // 2025: ₱2,000,000 of taxable income before the loss.
    corporateYear($this->corporation, 2025, 1_000_000, 500_000);
    $tax = app(CorporateTax::class);

    expect($tax->annual($this->corporation, 2024))->toMatchArray(['uses' => 'mcit', 'mcit' => 8000.0, 'nolco' => 0.0, 'carried' => []]);

    $annual = $tax->annual($this->corporation, 2025);

    // ₱2,000,000 less the ₱1,200,000 loss, at 20%; the ₱8,000 credited.
    expect($annual)->toMatchArray([
        'nolco' => 1200000.0, 'net_taxable_income' => 800000.0, 'regular_tax' => 160000.0, 'uses' => 'regular',
        'excess_mcit_applied' => 8000.0, 'quarterly_payments' => 300000.0, 'payable' => -148000.0,
    ])->and($annual['carried'])->toBe([
        ['kind' => 'nolco', 'year' => 2024, 'available' => 1200000.0, 'applied' => 1200000.0, 'last_year' => 2027],
        ['kind' => 'excess_mcit', 'year' => 2024, 'available' => 8000.0, 'applied' => 8000.0, 'last_year' => 2027],
    ])->and(app(Form1702RTItems::class)->values($this->corporation, $annual))->toMatchArray([
        '36' => 1200000, '37' => 3200000, '39' => 800000, '41' => 160000, '47' => 8000,
    ]);

    // Used up, nothing reaches 2026.
    corporateYear($this->corporation, 2026, 1_000_000, 500_000);
    expect($tax->annual($this->corporation, 2026)['carried'])->toBe([]);
});

test('a 2021 loss carries five years; a later one, three', function () {
    corporateYear($this->corporation, 2021, 100_000, 300_000);
    corporateYear($this->corporation, 2023, 100_000, 300_000);
    corporateYear($this->corporation, 2024, 100_000, 100_000);

    $carried = collect(app(CorporateTax::class)->annual($this->corporation, 2024)['carried'])->where('kind', 'nolco')->values();

    expect($carried->pluck('last_year', 'year')->all())->toBe([2021 => 2026, 2023 => 2026]);
});

test('a quarter with nothing to pay still has its 1702Q, and none before the corporation registered', function () {
    corporateYear($this->corporation, 2026, 100_000, 400_000);
    $this->corporation->update(['operations_began' => 2026]);
    $profile = TaxProfile::factory()->create();
    $deadlines = fn (int $year) => collect(app(TaxObligations::class)->deadlines($profile, app(TaxCalculationService::class)->forYear($profile, $year)));

    expect($deadlines(2026)->firstWhere('title', 'Q1 1702Q · Bayani Creative Studio Inc.'))->toMatchArray(['amount' => 0.0, 'note' => 'No payment'])
        ->and($deadlines(2025)->filter(fn (array $d) => str_contains($d['title'], '1702')))->toBeEmpty();
});
