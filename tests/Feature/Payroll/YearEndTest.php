<?php

use App\Compliance\Services\TaxObligations;
use App\Data\Services\LocalData;
use App\Payroll\Models\Employee;
use App\Payroll\Services\Payroll;
use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\Items\Form1604CItems;
use App\Returns\Forms\Items\Form2316Items;
use App\Returns\Models\Filing;
use App\Tax\Services\TaxCalculationService;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-05 10:00'));
    TaxProfile::factory()->create();
    $this->joy = Employee::query()->create([
        'first_name' => 'Joy', 'middle_name' => 'A.', 'last_name' => 'Ramos', 'tin' => '345-678-901-000',
        'monthly_pay' => 30_000, 'started_on' => '2026-10-01',
    ]);
});

function yearEndItems(): array
{
    $profile = TaxProfile::current();
    $year = app(TaxCalculationService::class)->forYear($profile, 2026);

    return array_values(array_filter(
        app(TaxObligations::class)->deadlines($profile, $year),
        // The BIR's scanned copies are their own reminder, tested with the 1601-C dates.
        fn (array $d) => (str_contains($d['title'], '2316') && ! str_contains($d['title'], 'scanned')) || str_contains($d['title'], '1604-C'),
    ));
}

test('Joy’s 2316 shows her pay and, after the refund, no tax withheld', function () {
    $pay = app(Payroll::class)->employeeYear($this->joy, 2026);

    expect(app(Form2316Items::class)->values(TaxProfile::current(), $this->joy, 2026, $pay))->toMatchArray([
        '1' => '2026', '2.from' => '10/01', '2.to' => '12/31',
        '3.1' => '345', '3.3' => '901', '3.branch' => '00000', '4' => 'RAMOS, JOY A.',
        '12.1' => '123', '12.branch' => '00000', '15.main' => true,
        '19' => 90000.0, '21' => 90000.0, '23' => 90000.0, '24' => 0.0, '25A' => 0.0, '26' => 0.0, '28' => 0.0,
        '39' => 90000.0, '52' => 90000.0,
    ]);

    $this->get("/payroll/employees/{$this->joy->id}/2316")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('payroll/certificate')
        ->where('year', 2026)
        ->where('pay.refund', 2750)
        ->where('givenOn', null)
        ->where('dueDate', '2027-02-01')
        ->where('refundBy', '2027-01-25'));

    $response = $this->get("/payroll/employees/{$this->joy->id}/2316/2026/pdf")->assertOk();
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
    $this->get("/payroll/employees/{$this->joy->id}/2316/2025")->assertNotFound();
});

test('the 1604-C lists each month and the refund as an over-remittance', function () {
    $this->post('/filings', [
        'year' => 2026, 'quarter' => 4, 'month' => 10, 'form' => '1601-C', 'filed_on' => '2026-11-05',
        'paid' => true, 'amount_paid' => '1375', 'paid_on' => '2026-11-05', 'payment_channel' => 'online',
    ])->assertSessionHasNoErrors();
    $annual = app(Payroll::class)->annual(2026);

    expect($annual)->toMatchArray([
        'compensation' => 90000.0, 'withheld' => 2750.0, 'refund' => 2750.0, 'due_date' => '2027-02-01', 'status' => 'open',
    ])->and(app(Form1604CItems::class)->values(TaxProfile::current(), $annual))->toMatchArray([
        '1' => '26', '2.no' => true, '8.private' => true, '11.yes' => true, '12' => 2750.0, '13' => '01',
        'm.10.date' => '11/05/2026', 'm.10.withheld' => 1375.0, 'm.10.remitted' => 1375.0,
        'm.11.withheld' => 1375.0, 'm.12.withheld' => 0.0, 'm.total.withheld' => 2750.0, 'm.total.remitted' => 2750.0,
    ]);

    $this->get('/payroll/2026/annual')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('payroll/annual')->has('annual.employees', 1));
    $response = $this->get('/payroll/2026/annual/pdf')->assertOk();
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
    $this->get('/payroll/2025/annual')->assertNotFound();
});

test('the calendar asks for the 2316 and the 1604-C until they are done', function () {
    expect(array_column(yearEndItems(), 'title'))->toBe(['Give Joy Ramos their 2316', '2026 1604-C · list of employees'])
        ->and(yearEndItems()[1])->toMatchArray(['date' => '2027-02-01', 'kind' => 'withholding', 'href' => '/payroll/2026/annual']);

    $this->post("/payroll/employees/{$this->joy->id}/2316/2026/given")->assertRedirect();
    $this->post("/payroll/employees/{$this->joy->id}/2316/2026/given");
    expect(app(Payroll::class)->annual(2026)['employees'][0]['given_on'])->toBe('2026-11-05')
        ->and(array_column(yearEndItems(), 'title'))->toBe(['2026 1604-C · list of employees']);

    // The 1604-C is filed once the year is over, with no payment.
    $filing = ['year' => 2026, 'quarter' => 4, 'form' => '1604-C', 'filed_on' => '2026-11-05', 'paid' => false];
    $this->post('/filings', $filing)->assertSessionHasErrors();
    $this->travelTo(CarbonImmutable::parse('2027-01-20 10:00'));
    $this->post('/filings', [...$filing, 'filed_on' => '2027-01-20'])->assertSessionHasNoErrors();

    expect(Filing::query()->where('form', '1604-C')->sole()->snapshot)->toBeNull()
        ->and(yearEndItems())->toBe([])
        ->and(app(LocalData::class)->rows()['employee_certificates'])->toHaveCount(1);
});
