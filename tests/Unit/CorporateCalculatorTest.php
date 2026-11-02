<?php

use App\Corporations\Services\CorporateCalculator;
use App\Corporations\Services\CorporateInputs;
use Carbon\CarbonImmutable;

/*
 * The corporate calculator on its own: no app, no database, no config.
 */

function corporateInputs(array $rows, bool $mcitApplies = true): CorporateInputs
{
    $dates = [];

    foreach ([1 => '2026-05-30', 2 => '2026-08-29', 3 => '2026-11-29', 4 => '2027-04-15'] as $quarter => $due) {
        $dates[$quarter] = [
            'statutory' => CarbonImmutable::parse($due), 'due' => CarbonImmutable::parse($due), 'due_note' => '',
            'period_end' => CarbonImmutable::create(2026, $quarter * 3)->endOfMonth()->startOfDay(),
        ];
    }

    return new CorporateInputs(
        year: 2026,
        today: CarbonImmutable::parse('2026-10-27'),
        deductionMethod: 'itemized',
        totalAssets: 30_000_000,
        mcitApplies: $mcitApplies,
        rows: array_map(fn (array $r) => [
            'sales' => (float) $r[0], 'cost_of_sales' => 0.0, 'other_income' => 0.0, 'deductions' => (float) $r[1], 'withheld' => 0.0,
            'filed_on' => null, 'reference_number' => null, 'amount_paid' => null,
        ], $rows),
        rules: [
            'rate' => 0.25, 'small_rate' => 0.20, 'mcit_rates' => array_fill(1, 12, 0.02),
            'small_taxable_income' => 5_000_000, 'small_total_assets' => 100_000_000, 'osd_rate' => 0.40,
        ],
        dates: $dates,
    );
}

test('a small corporation pays 20% on taxable income, or the MCIT when that’s higher', function () {
    $quarters = (new CorporateCalculator)->quarters(corporateInputs([1 => [1_000_000, 400_000], 2 => [100_000, 300_000]]), 2);

    // Q1: ₱600,000 at 20% beats 2% of ₱1M. Q2 to date: ₱400,000 at 20% = ₱80,000 vs MCIT ₱22,000.
    expect($quarters[1])->toMatchArray(['small' => true, 'regular_tax' => 120000.0, 'mcit' => 20000.0, 'uses' => 'regular', 'payable' => 120000.0, 'status' => 'overdue'])
        ->and($quarters[2])->toMatchArray(['regular_tax' => 80000.0, 'mcit' => 22000.0, 'paid_regular' => 120000.0, 'payable' => -40000.0]);
});

test('a loss year carries its loss and its MCIT into the next', function () {
    $loss = (new CorporateCalculator)->year(corporateInputs([1 => [100_000, 400_000], 2 => [100_000, 400_000], 3 => [100_000, 400_000], 4 => [100_000, 400_000]]), []);

    expect($loss['annual'])->toMatchArray(['uses' => 'mcit', 'mcit' => 8000.0])
        ->and($loss['ledger'])->toBe([
            ['kind' => 'nolco', 'year' => 2026, 'remaining' => 1200000.0, 'last_year' => 2029],
            ['kind' => 'excess_mcit', 'year' => 2026, 'remaining' => 8000.0, 'last_year' => 2029],
        ]);
});
