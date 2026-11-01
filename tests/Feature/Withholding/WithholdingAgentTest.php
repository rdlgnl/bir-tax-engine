<?php

use App\Compliance\Services\TaxObligations;
use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\Items\Form0619EItems;
use App\Returns\Forms\Items\Form1601EQItems;
use App\Returns\Forms\Items\Form2307Items;
use App\Returns\Models\Filing;
use App\Tax\Services\FilingCalendar;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Enums\TransactionType;
use App\Transactions\Models\Transaction;
use App\Withholding\Models\IssuedCertificate;
use App\Withholding\Services\WithholdingAgent;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-20 10:00'));
});

/**
 * Ella's Design Studio from the design: rent to Cruz Properties every month
 * of Q3 and a design fee to Leo Bautista in August.
 */
function withholdingAgent(): TaxProfile
{
    $profile = TaxProfile::factory()->create([
        'withholding_agent' => true, 'trade_name' => "Ella's Design Studio", 'tin' => '456-789-012-000', 'rdo_code' => '039',
    ]);

    $paid = fn (string $date, string $payee, string $atc, float $amount, float $withheld, ?string $tin = null) => Transaction::query()->create([
        'type' => TransactionType::Expense, 'occurred_on' => $date, 'counterparty' => $payee, 'counterparty_tin' => $tin,
        'category' => $atc === 'WI010' ? 'person' : 'rent', 'amount' => $amount, 'atc' => $atc, 'agent_withheld' => $withheld,
    ]);

    $paid('2026-07-05', 'Cruz Properties', 'WC100', 30_000, 1_500, '222-333-444-000');
    $paid('2026-08-05', 'Cruz Properties', 'WC100', 30_000, 1_500, '222-333-444-000');
    $paid('2026-08-20', 'Leo Bautista', 'WI010', 40_000, 2_000);
    $paid('2026-09-05', 'Cruz Properties', 'WC100', 30_000, 1_500, '222-333-444-000');

    return $profile;
}

function remitted(int $month, float $amount): Filing
{
    return Filing::query()->create([
        'year' => 2026, 'quarter' => (int) ceil($month / 3), 'month' => $month, 'form' => '0619-E',
        'filed_on' => '2026-'.str_pad((string) ($month + 1), 2, '0', STR_PAD_LEFT).'-08', 'amount_paid' => $amount,
    ]);
}

test('a quarter adds up what was withheld each month, less the 0619-E remittances', function () {
    withholdingAgent();
    remitted(7, 1_500);
    remitted(8, 3_500);

    $q3 = app(WithholdingAgent::class)->year(2026)[3];

    expect(array_column($q3['months'], 'withheld'))->toBe([1500.0, 3500.0, 1500.0])
        ->and(array_column($q3['months'], 'status'))->toBe(['filed', 'filed', 'open'])
        ->and($q3['months'][0]['due_date'])->toBe('2026-08-10')
        ->and($q3['months'][2]['due_date'])->toBeNull()
        ->and($q3)->toMatchArray([
            'withheld' => 6500.0, 'remitted' => 5000.0, 'still_due' => 1500.0, 'status' => 'due',
            'due_date' => app(FilingCalendar::class)->withholdingQuarterlyDue(2026, 3)->toDateString(),
        ])
        ->and(array_column($q3['payees'], 'name'))->toBe(['Cruz Properties', 'Leo Bautista'])
        ->and($q3['payees'][0])->toMatchArray([
            'key' => 'cruz-properties', 'tin' => '222-333-444-000', 'atc' => 'WC100',
            'income' => 90000.0, 'withheld' => 4500.0, 'months' => [30000.0, 30000.0, 30000.0], 'given_on' => null,
        ])
        ->and(array_column($q3['atcs'], 'withheld', 'atc'))->toBe(['WC100' => 4500.0, 'WI010' => 2000.0]);
});

test('an unremitted month stays due on the quarter and turns overdue after the 10th', function () {
    withholdingAgent();

    $q3 = app(WithholdingAgent::class)->year(2026)[3];

    expect($q3['months'][0]['status'])->toBe('overdue')
        ->and($q3)->toMatchArray(['remitted' => 0.0, 'still_due' => 6500.0]);
});

test('a withholding agent records the ATC and tax kept on an expense; others don’t', function () {
    withholdingAgent();
    $expense = [
        'type' => 'expense', 'occurred_on' => '2026-10-15', 'counterparty' => 'Mara Santos', 'category' => 'person',
        'amount' => '40000', 'atc' => 'WI010', 'agent_withheld' => '',
    ];

    $this->post('/transactions', $expense)->assertSessionHasNoErrors();

    expect(Transaction::query()->where('counterparty', 'Mara Santos')->sole())
        ->atc->toBe('WI010')->agent_withheld->toBe('2000.00')->tax_withheld->toBe('0.00');

    $this->post('/transactions', [...$expense, 'atc' => 'XX999'])->assertSessionHasErrors('atc');
    $this->post('/transactions', [...$expense, 'agent_withheld' => '50000'])->assertSessionHasErrors('agent_withheld');

    TaxProfile::current()->update(['withholding_agent' => false]);
    $this->post('/transactions', [...$expense, 'counterparty' => 'Kape Co'])->assertSessionHasNoErrors();

    expect(Transaction::query()->where('counterparty', 'Kape Co')->sole())
        ->atc->toBeNull()->agent_withheld->toBe('0.00');
});

test('a month’s 0619-E is marked remitted once, and only for the first two months of a quarter', function () {
    withholdingAgent();
    $filing = [
        'year' => 2026, 'quarter' => 3, 'month' => 8, 'form' => '0619-E', 'filed_on' => '2026-09-08',
        'reference_number' => '', 'paid' => true, 'amount_paid' => '3500', 'paid_on' => '2026-09-08', 'payment_channel' => 'online',
    ];

    $this->post('/filings', $filing)->assertSessionHasNoErrors()->assertRedirect();

    expect(Filing::query()->where('form', '0619-E')->sole())->month->toBe(8)->snapshot->toBeNull();

    $this->post('/filings', $filing)->assertSessionHasErrors('form');
    $this->post('/filings', [...$filing, 'month' => 9])->assertSessionHasErrors('month');
    $this->post('/filings', [...$filing, 'month' => 4])->assertSessionHasErrors('month');
    $this->post('/filings', [...$filing, 'month' => null])->assertSessionHasErrors('month');

    $this->post('/filings', [...$filing, 'month' => 7])->assertSessionHasNoErrors();
    $this->post('/filings', [...$filing, 'form' => '1601-EQ', 'month' => null, 'amount_paid' => '1500'])->assertSessionHasNoErrors();

    expect(app(WithholdingAgent::class)->year(2026)[3]['status'])->toBe('filed');
});

test('the withholding pages are only for a withholding agent', function () {
    withholdingAgent();
    remitted(7, 1_500);

    $this->get('/withholding/2026')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('withholding/index')
        ->has('quarters', 4)
        ->has('payees', 2)
        ->where('payees.0.name', 'Cruz Properties')
        ->where('payees.0.quarters.0.quarter', 3));

    $this->get('/withholding/2026/month/8')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('withholding/month')
        ->where('withheld', 3500)
        ->has('payments', 2)
        ->where('status.status', 'overdue')
        ->where('agent.name', "Ella's Design Studio"));

    $this->get('/withholding/2026/quarter/3')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('withholding/quarter')
        ->where('still_due', 5000)
        ->where('qapFile', '45678901200000920261601EQ.DAT')
        ->has('payees', 2));

    $this->get('/withholding/2026/quarter/3/form')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('withholding/quarter-form'));

    // The quarter's last month goes on the 1601-EQ, and the future isn't here yet.
    $this->get('/withholding/2026/month/9')->assertNotFound();
    $this->get('/withholding/2026/month/11')->assertNotFound();
    $this->get('/withholding/2027')->assertNotFound();

    TaxProfile::current()->update(['withholding_agent' => false]);
    $this->get('/withholding/2026')->assertNotFound();
});

test('each payee’s 2307 is filled and can be marked given', function () {
    withholdingAgent();

    $this->get('/withholding/2026/quarter/3/2307/leo-bautista')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('withholding/certificate')
        ->where('payee.name', 'Leo Bautista')
        ->where('payee.months', [0, 40000, 0])
        ->where('payee.given_on', null));
    $this->get('/withholding/2026/quarter/3/2307/nobody')->assertNotFound();

    $this->post('/withholding/2026/quarter/3/2307/leo-bautista/given')->assertRedirect();
    $this->post('/withholding/2026/quarter/3/2307/leo-bautista/given');

    expect(IssuedCertificate::query()->sole())->payee->toBe('Leo Bautista')->quarter->toBe(3)
        ->and(app(WithholdingAgent::class)->year(2026)[3]['payees'][1]['given_on'])->toBe('2026-10-20');
});

test('the 0619-E, 1601-EQ and 2307 are filled from the payments', function () {
    $profile = withholdingAgent();
    remitted(7, 1_500);
    remitted(8, 3_500);
    $agent = app(WithholdingAgent::class);
    $q3 = $agent->year(2026)[3];

    expect(app(Form0619EItems::class)->values($profile, 2026, 8, $agent->month(2026, 8)['withheld'], CarbonImmutable::parse('2026-09-10')))
        ->toMatchArray(['1' => '082026', '2' => '09102026', '4.yes' => true, '14' => 3500.0, '18' => 3500.0, 'tin.1' => '456', '8' => '039'])
        ->and(app(Form1601EQItems::class)->values($profile, 2026, $q3))->toMatchArray([
            '1' => '2026', '2.3' => true,
            '13.atc' => 'WC100', '13.base' => 90000.0, '13.rate' => '5%', '13.withheld' => 4500.0,
            '14.atc' => 'WI010', '14.base' => 40000.0, '14.withheld' => 2000.0,
            '19' => 6500.0, '20' => 1500.0, '21' => 3500.0, '25' => 5000.0, '26' => 1500.0,
        ])
        ->and(app(Form2307Items::class)->values($profile, 2026, 3, $q3['payees'][0]))->toMatchArray([
            '1.from' => '07012026', '1.to' => '09302026', 'payee.tin.1' => '222', 'payee.name' => 'CRUZ PROPERTIES',
            'payor.name' => "ELLA'S DESIGN STUDIO", 'row1.atc' => 'WC100', 'row1.month2' => 30000.0, 'total.withheld' => 4500.0,
        ]);

    foreach (['/withholding/2026/month/8/pdf', '/withholding/2026/quarter/3/pdf', '/withholding/2026/quarter/3/2307/cruz-properties/pdf'] as $url) {
        $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
    }

    expect($this->get('/withholding/2026/month/8/pdf?print=1')->headers->get('Content-Disposition'))->toStartWith('inline');
});

test('the quarterly list of payees exports in the alphalist layout and as CSV', function () {
    withholdingAgent();

    $response = $this->get('/withholding/2026/quarter/3/qap/dat')->assertOk()
        ->assertDownload('45678901200000920261601EQ.DAT');
    $lines = explode("\r\n", trim($response->getContent()));

    expect($lines)->toHaveCount(4)
        ->and($lines[0])->toStartWith('H1601EQ,456789012,0000,"ELLA\'S DESIGN STUDIO"')
        ->and($lines[1])->toBe('D1601EQ,1,222333444,0000,"CRUZ PROPERTIES","","","",09/2026,WC100,5.00,90000.00,4500.00')
        ->and($lines[2])->toStartWith('D1601EQ,2,,0000,"LEO BAUTISTA"')
        ->and($lines[3])->toBe('C1601EQ,456789012,0000,09/2026,130000.00,6500.00');

    $csv = $this->get('/withholding/2026/quarter/3/qap/csv')->assertOk()->getContent();

    expect($csv)->toContain('Payee,TIN,ATC')->toContain('Leo Bautista');
});

test('0619-E, 1601-EQ and 1604-E deadlines show on the calendar for a withholding agent', function () {
    $profile = withholdingAgent();
    remitted(7, 1_500);

    $year = app(TaxCalculationService::class)->forYear($profile, 2026, CarbonImmutable::now());
    $withholding = array_values(array_filter(
        app(TaxObligations::class)->deadlines($profile, $year),
        fn (array $item) => $item['kind'] === 'withholding',
    ));

    expect(array_column($withholding, 'title'))->toBe(['August 0619-E · withholding', 'Q3 1601-EQ · withholding', '2026 1604-E · list of payees'])
        ->and($withholding[0])->toMatchArray(['status' => 'overdue', 'amount' => 3500.0, 'href' => '/withholding/2026/month/8'])
        ->and($withholding[1])->toMatchArray(['amount' => 5000.0, 'href' => '/withholding/2026/quarter/3'])
        ->and($withholding[2])->toMatchArray(['date' => '2027-03-01', 'status' => 'open', 'amount' => null, 'href' => '/withholding/2026/annual']);
});

test('a month’s 0619-E can be filed once the month is over, before its quarter ends', function () {
    withholdingAgent();
    $this->travelTo(CarbonImmutable::parse('2026-11-08 10:00'));
    $filing = [
        'year' => 2026, 'quarter' => 4, 'month' => 10, 'form' => '0619-E', 'filed_on' => '2026-11-08',
        'reference_number' => '', 'paid' => true, 'amount_paid' => '0', 'paid_on' => '2026-11-08', 'payment_channel' => 'online',
    ];

    $this->post('/filings', $filing)->assertSessionHasNoErrors();
    $this->post('/filings', [...$filing, 'month' => 11])->assertSessionHasErrors('quarter');
});
