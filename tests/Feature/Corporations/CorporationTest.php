<?php

use App\Compliance\Services\TaxObligations;
use App\Corporations\Models\Corporation;
use App\Corporations\Services\CorporateTax;
use App\Data\Services\LocalData;
use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\Items\Form1702QItems;
use App\Returns\Forms\Items\Form1702RTItems;
use App\Tax\Services\TaxCalculationService;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-01 10:00'));
    $this->seed(DemoTaxpayerSeeder::class);
});

/**
 * Design 13-08..10: Bayani Creative Studio Inc., operating since 2022,
 * with Q1 and Q2 filed and paid (₱160,000 between them) and Q3 to file.
 */
function bayani(array $attributes = []): Corporation
{
    $corporation = Corporation::query()->create([
        'name' => 'Bayani Creative Studio Inc.',
        'tin' => '789-012-345-000',
        'rdo_code' => '044',
        'operations_began' => 2022,
        'total_assets' => 30_000_000,
        'deduction_method' => 'itemized',
        'vat_registered' => true,
        'withholding_agent' => true,
        'employees' => 5,
        ...$attributes,
    ]);

    foreach ([[1, 700_000, 25_000, 75_000], [2, 650_000, 25_000, 85_000], [3, 750_000, 40_000, null]] as [$quarter, $deductions, $withheld, $paid]) {
        $row = $corporation->quarters()->create([
            'year' => 2026,
            'quarter' => $quarter,
            'sales' => 3_000_000,
            'cost_of_sales' => 1_800_000,
            'other_income' => 0,
            'deductions' => $deductions,
            'withheld' => $withheld,
        ]);

        if ($paid !== null) {
            $row->forceFill(['filed_on' => "2026-0{$quarter}-28", 'amount_paid' => $paid])->save();
        }
    }

    return $corporation;
}

test('the Q3 1702Q adds up the year to date, on the 20% rate (design 13-09)', function () {
    $tax = app(CorporateTax::class)->quarter(bayani(), 2026, 3);

    expect($tax['to_date'])->toMatchArray([
        'sales' => 9_000_000.0,
        'cost_of_sales' => 5_400_000.0,
        'gross_income' => 3_600_000.0,
        'deductions' => 2_100_000.0,
        'taxable_income' => 1_500_000.0,
    ])->and($tax)->toMatchArray([
        'previous_taxable_income' => 1_050_000.0,
        'rate' => 0.20,
        'small' => true,
        'regular_tax' => 300_000.0,
        'mcit_applies' => true,
        'mcit' => 72_000.0,
        'uses' => 'regular',
        'tax_due' => 300_000.0,
        'paid_regular' => 160_000.0,
        'withheld_previous' => 50_000.0,
        'withheld_this_quarter' => 40_000.0,
        'payable' => 50_000.0,
        'due_date' => '2026-12-01',
        'due_note' => '60 days after Sep 30 is Sun, Nov 29; Nov 30 is Bonifacio Day, so it moves to Dec 1.',
        'status' => 'due',
    ]);
});

test('the rate is 25% above ₱100M in assets, and MCIT waits for the 4th year', function () {
    $tax = app(CorporateTax::class)->quarter(bayani(['total_assets' => 150_000_000, 'operations_began' => 2023]), 2026, 3);

    expect($tax)->toMatchArray(['rate' => 0.25, 'small' => false, 'regular_tax' => 375_000.0, 'mcit_applies' => false, 'mcit' => 0.0])
        ->and(app(CorporateTax::class)->mcitFrom(Corporation::current()))->toBe(2027);
});

test('MCIT is due when it’s higher than the regular tax, and is credited as MCIT', function () {
    $corporation = bayani();
    $corporation->quarters()->update(['deductions' => 1_150_000, 'withheld' => 0, 'filed_on' => null, 'amount_paid' => null]);
    $tax = app(CorporateTax::class)->quarter($corporation, 2026, 3);

    // Taxable ₱150,000 to date: 20% is ₱30,000, below 2% of ₱3.6M gross.
    // Q1 and Q2 each came to ₱24,000 of MCIT.
    expect($tax)->toMatchArray([
        'regular_tax' => 30_000.0,
        'mcit' => 72_000.0,
        'uses' => 'mcit',
        'tax_due' => 72_000.0,
        'paid_regular' => 0.0,
        'paid_mcit' => 48_000.0,
        'payable' => 24_000.0,
    ]);
});

test('the optional standard deduction takes 40% of gross income in place of the books’ deductions', function () {
    $tax = app(CorporateTax::class)->quarter(bayani(['deduction_method' => 'osd']), 2026, 3);

    expect($tax['to_date'])->toMatchArray(['deductions' => 1_440_000.0, 'taxable_income' => 2_160_000.0]);
});

test('the annual return is estimated as if Q4 goes like Q1 to Q3 (design 13-08)', function () {
    $annual = app(CorporateTax::class)->annual(bayani(), 2026);

    expect($annual)->toMatchArray([
        'estimated' => true,
        'rate' => 0.20,
        'tax_due' => 400_000.0,
        'mcit' => 96_000.0,
        'quarterly_payments' => 210_000.0,
        'withheld' => 120_000.0,
        'payable' => 70_000.0,
        'due_date' => '2027-04-15',
    ])->and($annual['figures'])->toMatchArray(['sales' => 12_000_000.0, 'gross_income' => 4_800_000.0, 'taxable_income' => 2_000_000.0])
        ->and($annual)->toMatchArray([
            'paid_regular' => 210_000.0,
            'paid_mcit' => 0.0,
            'withheld_previous' => 90_000.0,
            'withheld_fourth_quarter' => 30_000.0,
            'status' => 'open',
            'filed_on' => null,
        ]);
});

test('the filled 1702-RT carries the year’s computation and credits (design 13-11)', function () {
    $corporation = bayani();
    $values = app(Form1702RTItems::class)->values($corporation, app(CorporateTax::class)->annual($corporation, 2026));

    expect($values)->toMatchArray([
        '2.year' => '26',
        '3.no' => true,
        'atc.code' => 'IC010',
        '8.1' => 'BAYANI CREATIVE STUDIO INC.',
        '13.itemized' => true,
        '27' => 12_000_000,
        '30' => 7_200_000,
        '33' => 4_800_000,
        '34' => 2_800_000,
        '37' => 2_800_000,
        '39' => 2_000_000,
        '40' => '20',
        '41' => 400_000,
        '42' => 96_000,
        '43' => 400_000,
        '45' => 0,
        '46' => 210_000,
        '48' => 90_000,
        '49' => 30_000,
        '55' => 330_000,
        '56' => 70_000,
        '14' => 400_000,
        '15' => 330_000,
        '16' => 70_000,
        '21' => 70_000,
        's1.18' => 2_800_000,
        'p4.name' => 'BAYANI CREATIVE STUDIO INC.',
    ]);
});

test('after the year, Q4’s figures make the 1702-RT final, and it’s filed', function () {
    bayani();
    $this->travelTo(CarbonImmutable::parse('2027-02-01 10:00'));

    $this->get('/corporation')->assertInertia(fn (Assert $page) => $page->where('annual.year', 2026)->where('annual.estimated', true)->where('annual.status', 'due'));
    // Q4 has no 1702Q: the year's 1702-RT covers it.
    $this->get('/corporation/2026/4')->assertMethodNotAllowed();
    $this->post('/corporation/2026/annual/filed', ['filed_on' => '2027-02-01'])->assertNotFound();

    $this->put('/corporation/2026/4', [
        'sales' => 3_500_000, 'cost_of_sales' => 2_000_000, 'other_income' => 0, 'deductions' => 900_000, 'withheld' => 35_000,
    ])->assertRedirect();

    // Taxable ₱2,100,000 for the year: ₱420,000, less ₱210,000 paid and ₱125,000 withheld.
    $this->get('/corporation/2026/annual')->assertInertia(fn (Assert $page) => $page
        ->component('corporation/annual')
        ->where('annual.estimated', false)
        ->where('annual.figures.taxable_income', 2100000)
        ->where('annual.payable', 85000)
        ->where('figures.withheld', '35000.00'));
    expect($this->get('/corporation/2026/annual/pdf')->assertOk()->getContent())->toStartWith('%PDF');

    $profile = TaxProfile::current();
    $deadlines = app(TaxObligations::class)->deadlines($profile, app(TaxCalculationService::class)->forYear($profile, 2026));
    expect(collect($deadlines)->firstWhere('title', '2026 1702-RT · Bayani Creative Studio Inc.'))
        ->toMatchArray(['date' => '2027-04-15', 'status' => 'due', 'amount' => 85000.0, 'href' => '/corporation/2026/annual']);

    $this->post('/corporation/2026/annual/filed', ['filed_on' => '2027-02-01', 'amount_paid' => 85_000])->assertRedirect();

    expect(app(CorporateTax::class)->annual(Corporation::current(), 2026))->toMatchArray(['status' => 'filed', 'amount_paid' => 85000.0]);
    $this->get('/corporation')->assertInertia(fn (Assert $page) => $page->where('annual', null));
});

test('the filled 1702Q carries the quarter, the schedules and the credits (design 13-10)', function () {
    $corporation = bayani();
    $values = app(Form1702QItems::class)->values($corporation, app(CorporateTax::class)->quarter($corporation, 2026, 3));

    expect($values)->toMatchArray([
        '2.year' => '26',
        '3.3' => true,
        'atc.code' => 'IC010',
        'atc.mcit' => false,
        'tin.1' => '789',
        '7' => '044',
        '8.1' => 'BAYANI CREATIVE STUDIO INC.',
        's2.1' => 3_000_000,
        's2.7' => 450_000,
        's2.8' => 1_050_000,
        's2.9' => 1_500_000,
        's2.10' => '20',
        's2.11' => 300_000,
        's2.12' => 72_000,
        's2.13' => 300_000,
        's3.4' => 3_600_000,
        's3.6' => 72_000,
        's4.2' => 160_000,
        's4.4' => 50_000,
        's4.5' => 40_000,
        's4.7' => 250_000,
        '14' => 300_000,
        '20' => 50_000,
        '25' => 50_000,
    ]);
});

test('the corporation is set up, shown, filled in, filed, on the calendar and in backups', function () {
    $this->get('/corporation')->assertRedirect('/corporation/edit');

    $this->put('/corporation', [
        'name' => 'Bayani Creative Studio Inc.',
        'tin' => '789-012-345',
        'rdo_code' => '044',
        'operations_began' => 2022,
        'total_assets' => 30_000_000,
        'deduction_method' => 'itemized',
        'vat_registered' => true,
        'withholding_agent' => true,
        'employees' => 5,
    ])->assertRedirect('/corporation');

    foreach ([[1, 700_000, 25_000], [2, 650_000, 25_000], [3, 750_000, 40_000]] as [$quarter, $deductions, $withheld]) {
        $this->put("/corporation/2026/{$quarter}", [
            'sales' => 3_000_000, 'cost_of_sales' => 1_800_000, 'other_income' => 0, 'deductions' => $deductions, 'withheld' => $withheld,
        ])->assertRedirect();
    }

    $this->post('/corporation/2026/1/filed', ['filed_on' => '2026-05-28', 'amount_paid' => 75_000])->assertRedirect();
    $this->post('/corporation/2026/2/filed', ['filed_on' => '2026-08-28', 'amount_paid' => 85_000])->assertRedirect();

    $this->get('/corporation')->assertInertia(fn (Assert $page) => $page
        ->component('corporation/show')
        ->where('next.quarter', 3)
        ->where('next.payable', 50000)
        ->where('soFar.to_date.taxable_income', 1500000)
        ->where('annual.payable', 70000)
        ->where('rateNote.title', 'Why 20%, not 25%')
        ->where('rateNote.body', fn (string $body) => str_contains($body, 'On the quarterly return the 20% is provisional'))
        ->where('mcitNote.title', 'Minimum corporate income tax applies from 2026')
        ->where('mcitNote.body', '2026 is the 4th year after it registered with the BIR (2022), so each quarter compares 2% of gross income with the regular tax.')
        ->where('alsoDue', [
            ['title' => 'October withholding (0619-E)', 'detail' => 'Payments to suppliers', 'date' => '2026-11-10'],
            ['title' => 'October payroll (1601-C)', 'detail' => '5 employees', 'date' => '2026-11-10'],
            ['title' => 'Q4 VAT (2550Q)', 'detail' => 'Output VAT less input VAT', 'date' => '2027-01-25'],
        ]));

    $this->get('/corporation/2026/3')->assertInertia(fn (Assert $page) => $page
        ->component('corporation/quarter')
        ->where('tax.payable', 50000)
        ->where('figures.withheld', '40000.00'));

    $this->get('/corporation/2026/3/form')->assertInertia(fn (Assert $page) => $page->component('corporation/form')->where('annualDue', '2027-04-15'));
    expect($this->get('/corporation/2026/3/pdf')->assertOk()->getContent())->toStartWith('%PDF');

    $profile = TaxProfile::current();
    $deadlines = app(TaxObligations::class)->deadlines($profile, app(TaxCalculationService::class)->forYear($profile, 2026));
    expect(collect($deadlines)->firstWhere('title', 'Q3 1702Q · Bayani Creative Studio Inc.'))
        ->toMatchArray(['date' => '2026-12-01', 'status' => 'due', 'amount' => 50000.0, 'href' => '/corporation/2026/3'])
        ->and(collect($deadlines)->pluck('title'))->not->toContain('Q1 1702Q · Bayani Creative Studio Inc.');

    $this->post('/corporation/2026/3/filed', ['filed_on' => '2026-11-01', 'reference_number' => 'EFPS-Q3', 'amount_paid' => 50_000])->assertRedirect();
    $this->get('/corporation')->assertInertia(fn (Assert $page) => $page->where('next', null));

    $backup = app(LocalData::class)->rows();
    expect($backup['corporations'])->toHaveCount(1)->and($backup['corporation_quarters'])->toHaveCount(3);

    $this->delete('/corporation')->assertRedirect('/home');
    expect(Corporation::query()->count())->toBe(0);
});

test('quarter figures can’t be entered for a year that hasn’t come', function () {
    bayani();

    $this->put('/corporation/2027/1', ['sales' => 1, 'cost_of_sales' => 0, 'other_income' => 0, 'deductions' => 0, 'withheld' => 0])->assertNotFound();
});
