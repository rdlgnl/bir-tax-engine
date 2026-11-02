<?php

use App\Profiles\Enums\IncomeSource;
use App\Tax\Calculation\TaxCalculator;
use App\Tax\Calculation\TaxInputs;
use App\Tax\Calculation\YearTaxpayer;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Enums\Regime;
use Carbon\CarbonImmutable;

/*
 * The calculator on its own: inputs built by hand, no app, no database,
 * no config. The same inputs always give the same result.
 */

function inputs(YearTaxpayer $taxpayer, array $sales, array $filings = []): TaxInputs
{
    $dates = [];

    foreach ([1 => '2026-05-15', 2 => '2026-08-17', 3 => '2026-11-16', 4 => '2027-04-15'] as $quarter => $due) {
        $dates[$quarter] = ['statutory' => CarbonImmutable::parse($due), 'due' => CarbonImmutable::parse($due), 'moved_reason' => null];
    }

    return new TaxInputs(
        year: 2026,
        today: CarbonImmutable::parse('2026-10-27'),
        taxpayer: $taxpayer,
        sales: array_map(fn (array $s) => [
            'amount' => $s[1] * 100, 'withheld' => ($s[2] ?? 0) * 100, 'confirmed' => true,
            'income_date' => CarbonImmutable::parse($s[0]), 'sales_tax_date' => CarbonImmutable::parse($s[0]),
        ], $sales),
        expenses: [],
        filings: $filings,
        rules: [
            'brackets' => [[0, 0, 0.00], [250_000, 0, 0.15], [400_000, 22_500, 0.20], [800_000, 102_500, 0.25], [2_000_000, 402_500, 0.30], [8_000_000, 2_202_500, 0.35]],
            'percentage_rates' => [1 => 0.03, 2 => 0.03, 3 => 0.03, 4 => 0.03],
            'eight_percent_rate' => 0.08,
            'exempt_amount' => 25_000_000,
            'gross_limit' => 300_000_000,
            'osd_rate' => 0.40,
            'classes' => ['micro' => 3_000_000, 'small' => 20_000_000, 'medium' => 1_000_000_000],
        ],
        dates: $dates,
        assignedClass: null,
        salary: null,
    );
}

test('8%, purely self-employed: the year so far less the ₱250,000 reduction', function () {
    $taxpayer = new YearTaxpayer(Regime::EightPercent, null, IncomeSource::Business, false);
    $year = (new TaxCalculator)->year(inputs($taxpayer, [['2026-02-10', 180_000, 3_000], ['2026-05-10', 220_000], ['2026-08-10', 200_000]], [
        1 => ['filed' => true, 'filed_on' => '2026-05-12', 'paid_on' => '2026-05-12', 'paid' => 0],
        2 => ['filed' => true, 'filed_on' => '2026-08-14', 'paid_on' => '2026-08-14', 'paid' => 400_000],
    ]));

    // ₱600,000 − ₱250,000 = ₱350,000 × 8% = ₱28,000, less ₱4,000 paid and ₱3,000 withheld.
    expect($year['quarters'][3])->toMatchArray(['gross' => 600000.0, 'taxable' => 350000.0, 'income_tax' => 28000.0, 'prior_payments' => 4000.0, 'withheld' => 3000.0, 'due' => 21000.0, 'percentage_tax' => null, 'form' => '1701Q'])
        ->and($year['quarters'][4]['form'])->toBe('1701-MS')
        ->and($year['classification'])->toBe('micro');
});

test('graduated with the 40% deduction, and its quarterly percentage tax', function () {
    $taxpayer = new YearTaxpayer(Regime::Graduated, DeductionMethod::Osd, IncomeSource::Business, false);
    $year = (new TaxCalculator)->year(inputs($taxpayer, [['2026-02-10', 600_000]]));

    // ₱600,000 less 40% = ₱360,000: 15% over ₱250,000.
    expect($year['quarters'][1])->toMatchArray(['deduction' => 240000.0, 'taxable' => 360000.0, 'income_tax' => 16500.0, 'percentage_tax' => 18000.0, 'percentage_tax_base' => 600000.0]);
});

test('each quarter carries the steps from gross to tax due, with the rule and rate each applies', function () {
    $taxpayer = new YearTaxpayer(Regime::Graduated, DeductionMethod::Osd, IncomeSource::Business, false);
    $in = inputs($taxpayer, [['2026-02-10', 600_000, 10_000]]);
    $in = new TaxInputs($in->year, $in->today, $in->taxpayer, $in->sales, $in->expenses, $in->filings,
        [...$in->rules, 'rule_ids' => ['graduated' => 'IT-GRAD-2023', 'percentage_tax' => [1 => 'PT-3PCT', 2 => 'PT-3PCT', 3 => 'PT-3PCT', 4 => 'PT-3PCT']]],
        $in->dates, null, null);

    $trace = collect((new TaxCalculator)->year($in)['quarters'][1]['trace'])->keyBy('step');

    expect($trace->keys()->all())->toBe(['gross', 'deduction', 'taxable', 'income_tax', 'prior_payments', 'withheld', 'due', 'percentage_tax'])
        ->and($trace['deduction'])->toMatchArray(['amount' => 240000.0, 'rule' => 'IT-OSD', 'rate' => 0.4])
        ->and($trace['income_tax'])->toMatchArray(['amount' => 16500.0, 'rule' => 'IT-GRAD-2023', 'note' => '15% of the excess over ₱250,000'])
        ->and($trace['withheld'])->toMatchArray(['amount' => 10000.0, 'rule' => 'CWT-CREDIT'])
        ->and($trace['due']['amount'])->toBe(6500.0)
        ->and($trace['percentage_tax'])->toMatchArray(['part' => 'percentage_tax', 'amount' => 18000.0, 'rule' => 'PT-3PCT', 'rate' => 0.03]);
});
