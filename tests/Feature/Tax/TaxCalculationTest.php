<?php

use App\Profiles\Models\TaxProfile;
use App\Returns\Models\Filing;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;

/*
 * The expected numbers are the ones on the approved screens
 * (design/screens/desktop/02-02-DeskTax.png and 02-03-DeskCompare.png).
 */

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->engine = app(TaxCalculationService::class);
});

test('Q3 income tax for the sample taxpayer matches the design', function () {
    $this->seed(DemoTaxpayerSeeder::class);

    $q3 = $this->engine->forYear(TaxProfile::current(), 2026)['quarters'][3];

    expect($q3)->toMatchArray([
        'form' => '1701Q',
        'gross' => 600_000.0,
        'exempt_amount' => 250_000.0,
        'taxable' => 350_000.0,
        'income_tax' => 28_000.0,
        'prior_payments' => 4_000.0,
        'withheld' => 10_000.0,
        'withheld_unconfirmed' => 1_000.0,
        'due' => 14_000.0,
        'quarter_gross' => 200_000.0,
        'quarter_withheld' => 2_000.0,
        'percentage_tax' => null,
        'statutory_date' => '2026-11-15',
        'due_date' => '2026-11-16',
        'due_date_moved_reason' => 'Nov 15 is a Sunday',
        'days_left' => 20,
        'status' => 'due',
    ]);
});

test('earlier quarters use the cumulative method and carry excess credit', function () {
    $this->seed(DemoTaxpayerSeeder::class);

    $year = $this->engine->forYear(TaxProfile::current(), 2026);

    expect($year['gross'])->toBe(655_000.0)
        ->and($year['status'])->toBe('ok')
        ->and($year['quarters'][1])->toMatchArray([
            'income_tax' => 0.0, 'due' => 0.0, 'excess_credit' => 3_000.0, 'status' => 'filed', 'amount_paid' => 0.0,
        ])
        ->and($year['quarters'][2])->toMatchArray([
            'income_tax' => 12_000.0, 'withheld' => 8_000.0, 'due' => 4_000.0, 'status' => 'filed', 'filed_on' => '2026-08-14',
        ])
        ->and($year['quarters'][4])->toMatchArray([
            'form' => '1701-MS', 'income_tax' => 32_400.0, 'prior_payments' => 18_000.0, 'withheld' => 11_500.0,
            'due' => 2_900.0, 'due_date' => '2027-04-15', 'status' => 'open',
        ]);
});

test('compare shows the three methods for ₱800,000 at 30% expenses', function () {
    $result = $this->engine->compare(800_000, 30);
    $options = collect($result['options'])->keyBy('key');

    expect($options['eight_percent'])->toMatchArray(['taxable' => 550_000.0, 'total' => 44_000.0])
        ->and($options['graduated_osd'])->toMatchArray([
            'taxable' => 480_000.0, 'income_tax' => 38_500.0, 'percentage_tax' => 24_000.0, 'total' => 62_500.0,
        ])
        ->and($options['graduated_itemized'])->toMatchArray([
            'taxable' => 560_000.0, 'income_tax' => 54_500.0, 'total' => 78_500.0,
        ])
        ->and($result['best'])->toBe('eight_percent')
        ->and($result['savings'])->toBe(18_500.0);
});

test('actual expenses at 50% cost ₱46,500 on graduated rates', function () {
    $options = collect($this->engine->compare(800_000, 50)['options'])->keyBy('key');

    expect($options['graduated_itemized'])->toMatchArray(['income_tax' => 22_500.0, 'total' => 46_500.0]);
});

test('earning ₱3.2M ends the 8% option', function () {
    $result = $this->engine->compare(3_200_000, 30);
    $options = collect($result['options'])->keyBy('key');

    expect($result['over_limit'])->toBeTrue()
        ->and($options['eight_percent']['available'])->toBeFalse()
        ->and($options['graduated_osd']['income_tax'])->toBe(382_500.0)
        ->and($result['best'])->toBe('graduated_osd');
});

test('a salary removes the ₱250,000 reduction and stacks graduated tax', function () {
    $options = collect($this->engine->compare(800_000, 30, 400_000)['options'])->keyBy('key');

    // 8% on the full ₱800,000. OSD income of ₱480,000 sits on top of the salary:
    // 20% on ₱400,000 plus 25% on ₱80,000.
    expect($options['eight_percent']['total'])->toBe(64_000.0)
        ->and($options['graduated_osd']['income_tax'])->toBe(100_000.0);
});

test('graduated rates add quarterly percentage tax', function () {
    $profile = TaxProfile::factory()->graduated(DeductionMethod::Osd)->create();
    Transaction::factory()->on('2026-02-10')->create(['amount' => 300_000]);
    Transaction::factory()->on('2026-05-10')->create(['amount' => 200_000]);

    $year = $this->engine->forYear($profile, 2026);

    // Q2: ₱500,000 less 40% = ₱300,000 taxable → 15% of ₱50,000.
    expect($year['quarters'][1]['percentage_tax'])->toBe(9_000.0)
        ->and($year['quarters'][2])->toMatchArray([
            'deduction' => 200_000.0, 'taxable' => 300_000.0, 'income_tax' => 7_500.0, 'percentage_tax' => 6_000.0,
        ]);
});

test('itemized deductions only count expenses with valid invoices', function () {
    $profile = TaxProfile::factory()->graduated(DeductionMethod::Itemized)->create();
    Transaction::factory()->on('2026-02-10')->create(['amount' => 600_000]);
    Transaction::factory()->on('2026-02-11')->expense(validInvoice: true)->create(['amount' => 100_000]);
    Transaction::factory()->on('2026-02-12')->expense(validInvoice: false)->create(['amount' => 50_000]);

    expect($this->engine->forYear($profile, 2026)['quarters'][1])
        ->toMatchArray(['deduction' => 100_000.0, 'taxable' => 500_000.0, 'income_tax' => 42_500.0]);
});

test('exactly ₱3,000,000 stays on 8%; a peso more moves the year to graduated rates', function () {
    $profile = TaxProfile::factory()->create();
    $sale = Transaction::factory()->on('2026-03-01')->create(['amount' => 3_000_000]);

    expect($this->engine->forYear($profile, 2026))->toMatchArray(['status' => 'ok', 'switched' => null]);

    $sale->update(['amount' => 3_000_001]);

    expect($this->engine->forYear($profile, 2026)['switched'])->toBe(['month' => 3, 'quarter' => 1, 'vat_from' => '2026-04-01', 'register_by' => '2026-04-30']);
});

test('each transaction shows how much it moved the year’s tax', function () {
    $this->seed(DemoTaxpayerSeeder::class);
    $transactions = Transaction::query()->forYear(2026)->get();

    $effects = $this->engine->transactionEffects(TaxProfile::current(), $transactions);
    $by = fn (string $date) => $effects[$transactions->first(fn ($t) => $t->occurred_on->toDateString() === $date)->id];

    expect($by('2026-10-06'))->toBe(2_000.0)
        // On 8% the unpaid Oct 26 invoice waits until it's paid.
        ->and($by('2026-10-26'))->toBe(0.0)
        ->and($by('2026-10-15'))->toBe(0.0)
        ->and($by('2026-01-12'))->toBe(0.0);
});

test('previewing a new payment shows its tax effect and credit', function () {
    $this->seed(DemoTaxpayerSeeder::class);

    $preview = $this->engine->previewEffect(
        TaxProfile::current(),
        Transaction::query()->forYear(2026)->get(),
        new Transaction(['type' => 'payment', 'occurred_on' => '2026-10-28', 'counterparty' => 'Lumen Digital', 'amount' => 30_000, 'tax_withheld' => 1_500]),
    );

    expect($preview)->toBe(['quarter' => 4, 'tax_effect' => 2_400.0, 'credit' => 1_500.0]);
});

test('entries on December 31 count in their year', function () {
    $profile = TaxProfile::factory()->create();
    Transaction::factory()->on('2026-12-31')->create(['amount' => 300_000]);
    Transaction::factory()->on('2027-01-01')->create(['amount' => 999_000]);

    expect($this->engine->forYear($profile, 2026)['gross'])->toBe(300_000.0);
});

test('an earlier quarter left unpaid isn’t credited once it’s overdue: its tax stays owed (M7)', function () {
    $this->travelTo(CarbonImmutable::parse('2026-06-01 10:00'));
    $profile = TaxProfile::factory()->create();
    // 8% of ₱250,000 over the reduction in Q1, and of ₱100,000 more in Q2.
    Transaction::factory()->on('2026-02-10')->create(['amount' => 500_000]);
    Transaction::factory()->on('2026-04-10')->create(['amount' => 100_000]);

    // June: Q1 (due May 15) is overdue and unpaid; Q2 credits nothing for it.
    expect($this->engine->forYear($profile, 2026)['quarters'][2])
        ->toMatchArray(['income_tax' => 28000.0, 'prior_payments' => 0.0, 'unpaid_earlier' => 20000.0, 'due' => 28000.0]);

    // Q1 filed and paid: credited.
    Filing::factory()->quarter(1, '2026-06-01', 20_000)->create(['year' => 2026]);
    expect($this->engine->forYear($profile, 2026)['quarters'][2])
        ->toMatchArray(['prior_payments' => 20000.0, 'unpaid_earlier' => 0.0, 'due' => 8000.0]);
});

test('an earlier quarter still payable on time counts as paid for the estimate', function () {
    $this->travelTo(CarbonImmutable::parse('2026-04-20 10:00'));
    $profile = TaxProfile::factory()->create();
    Transaction::factory()->on('2026-02-10')->create(['amount' => 500_000]);
    Transaction::factory()->on('2026-04-10')->create(['amount' => 100_000]);

    expect($this->engine->forYear($profile, 2026)['quarters'][2])->toMatchArray(['prior_payments' => 20000.0, 'unpaid_earlier' => 0.0, 'due' => 8000.0]);
});
