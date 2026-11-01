<?php

use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\Items\Form1604EItems;
use App\Returns\Models\Filing;
use App\Transactions\Enums\TransactionType;
use App\Transactions\Models\Transaction;
use App\Withholding\Services\WithholdingAgent;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-20 10:00'));
});

/**
 * Ella's Design Studio from the design: rent to Cruz Properties through the
 * year, and fees to Leo Bautista and Mara Santos.
 */
function annualAgent(): TaxProfile
{
    $profile = TaxProfile::factory()->create([
        'withholding_agent' => true, 'trade_name' => "Ella's Design Studio", 'tin' => '456-789-012-000', 'rdo_code' => '039',
    ]);

    $paid = fn (string $date, string $payee, string $atc, float $amount, float $withheld, ?string $tin = null) => Transaction::query()->create([
        'type' => TransactionType::Expense, 'occurred_on' => $date, 'counterparty' => $payee, 'counterparty_tin' => $tin,
        'category' => $atc === 'WC100' ? 'rent' : 'person', 'amount' => $amount, 'atc' => $atc, 'agent_withheld' => $withheld,
    ]);

    foreach (['01', '04', '07', '10'] as $month) {
        $paid("2026-{$month}-05", 'Cruz Properties', 'WC100', 30_000, 1_500, '222-333-444-000');
    }

    $paid('2026-08-20', 'Leo Bautista', 'WI010', 40_000, 2_000);
    $paid('2026-10-12', 'Mara Santos', 'WI010', 40_000, 2_000);

    return $profile;
}

test('the year lists each payee under their ATC, and counts a filed 1601-EQ as remitted', function () {
    annualAgent();
    Filing::query()->create(['year' => 2026, 'quarter' => 1, 'month' => 0, 'form' => '1601-EQ', 'filed_on' => '2026-04-28', 'amount_paid' => 1_500]);

    $annual = app(WithholdingAgent::class)->annual(2026);

    expect(array_column($annual['payees'], 'name'))->toBe(['Cruz Properties', 'Leo Bautista', 'Mara Santos'])
        ->and($annual['payees'][0])->toMatchArray([
            'atc' => 'WC100', 'rate' => 0.05, 'income' => 120000.0, 'withheld' => 6000.0, 'tin' => '222-333-444-000',
        ])
        ->and(array_column($annual['payees'][0]['quarters'], 'quarter'))->toBe([1, 2, 3, 4])
        ->and($annual)->toMatchArray([
            'income' => 200000.0, 'withheld' => 10000.0, 'remitted' => 1500.0, 'still_due' => 8500.0,
            'due_date' => '2027-03-01', 'status' => 'open', 'filed_on' => null,
        ]);
});

test('the 1604-E is filled with a line per quarter and the first payees', function () {
    $profile = annualAgent();
    Filing::query()->create(['year' => 2026, 'quarter' => 1, 'month' => 0, 'form' => '1601-EQ', 'filed_on' => '2026-04-28', 'amount_paid' => 1_500]);

    $values = app(Form1604EItems::class)->values($profile, app(WithholdingAgent::class)->annual(2026));

    expect($values)->toMatchArray([
        '1' => '26', '2.no' => true, 'tin.1' => '456', 'tin.branch' => '00000', '5' => '039', '8.private' => true,
        'p2.tin' => '45678901200000',
        's1.1.date' => '04/28/2026', 's1.1.withheld' => 1500.0, 's1.1.total' => 1500.0,
        's1.3.withheld' => 3500.0, 's1.4.withheld' => 3500.0, 's1.total.withheld' => 10000.0,
        's3.1.seq' => '1', 's3.1.name' => 'CRUZ PROPERTIES', 's3.1.atc' => 'WC100', 's3.1.rate' => '5%', 's3.1.income' => 120000.0,
        's3.3.name' => 'MARA SANTOS', 's3.total.income' => 200000.0, 's3.total.withheld' => 10000.0,
    ])->and($values['s1.3.date'])->toBeNull();

    $response = $this->get('/withholding/2026/annual/pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

test('the annual pages show the year, and the alphalist exports as a data file and CSV', function () {
    annualAgent();

    $this->get('/withholding/2026/annual')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('withholding/annual')
        ->has('payees', 3)
        ->where('withheld', 10000)
        ->where('alphalistFile', '4567890120000123120261604E.DAT')
        ->where('status', 'open'));

    $this->get('/withholding/2026/annual/form')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('withholding/annual-form')
        ->where('edition', 'January 2018 (ENCS)'));

    $response = $this->get('/withholding/2026/annual/alphalist/dat')->assertOk()->assertDownload('4567890120000123120261604E.DAT');
    $lines = explode("\r\n", trim($response->getContent()));

    expect($lines)->toHaveCount(5)
        ->and($lines[0])->toStartWith('H1604E,456789012,0000,"ELLA\'S DESIGN STUDIO"')
        ->and($lines[1])->toBe('D1604E,1,222333444,0000,"CRUZ PROPERTIES","","","",12/31/2026,WC100,5.00,120000.00,6000.00')
        ->and($lines[4])->toBe('C1604E,456789012,0000,12/31/2026,200000.00,10000.00');

    expect($this->get('/withholding/2026/annual/alphalist/csv')->assertOk()->getContent())
        ->toContain('Payee,TIN,ATC,Rate')->toContain('Mara Santos');

    TaxProfile::current()->update(['withholding_agent' => false]);
    $this->get('/withholding/2026/annual')->assertNotFound();
});

test('the 1604-E is marked filed with no payment, once the year is over', function () {
    annualAgent();
    $filing = ['year' => 2026, 'quarter' => 4, 'form' => '1604-E', 'filed_on' => '2026-10-20', 'reference_number' => 'EFPS-9', 'paid' => false];

    $this->post('/filings', $filing)->assertSessionHasErrors();

    $this->travelTo(CarbonImmutable::parse('2027-02-10 10:00'));
    $this->post('/filings', [...$filing, 'filed_on' => '2027-02-10'])->assertSessionHasNoErrors()->assertRedirect();

    expect(Filing::query()->where('form', '1604-E')->sole())
        ->amount_paid->toBeNull()->snapshot->toBeNull()->reference_number->toBe('EFPS-9')
        ->and(app(WithholdingAgent::class)->annual(2026))->toMatchArray(['status' => 'filed', 'filed_on' => '2027-02-10']);

    $this->post('/filings', [...$filing, 'filed_on' => '2027-02-10'])->assertSessionHasErrors('form');
});
