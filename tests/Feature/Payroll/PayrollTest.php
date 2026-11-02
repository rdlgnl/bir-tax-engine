<?php

use App\Compliance\Services\TaxObligations;
use App\Data\Services\LocalData;
use App\Payroll\Models\Employee;
use App\Payroll\Services\Payroll;
use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\Items\Form1601CItems;
use App\Returns\Models\Filing;
use App\Tax\Services\TaxCalculationService;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-05 10:00'));
    TaxProfile::factory()->create();
});

/** Design 9-05: Joy, a virtual assistant since Oct 1 at ₱30,000 a month. */
function joy(array $overrides = []): Employee
{
    return Employee::query()->create([
        'first_name' => 'Joy', 'middle_name' => 'A.', 'last_name' => 'Ramos', 'position' => 'Virtual assistant',
        'monthly_pay' => 30_000, 'started_on' => '2026-10-01', ...$overrides,
    ]);
}

test('each month withholds by the BIR table on the month’s taxable pay', function () {
    // RR 11-2018 Annex "E": 15% over ₱20,833; ₱1,875 + 20% over ₱33,333.
    expect(app(Payroll::class)->monthlyWithholding(30_000))->toBe(1375.05)
        ->and(app(Payroll::class)->monthlyWithholding(50_000))->toBe(5208.4)
        ->and(app(Payroll::class)->monthlyWithholding(20_000))->toBe(0.0);
});

test('the year-end adjustment refunds what was withheld when the year’s pay owes no tax', function () {
    $year = app(Payroll::class)->employeeYear(joy(), 2026);

    // Her 13th month for three months, ₱7,500, is paid with December's pay, untaxed.
    expect($year)->toMatchArray(['pay' => 97500.0, 'benefits' => 7500.0, 'benefits_exempt' => 7500.0, 'taxable' => 90000.0, 'tax' => 0.0, 'withheld' => 2750.1, 'refund' => 2750.1, 'method' => 'table'])
        ->and($year['months'])->toBe([
            ['month' => 10, 'pay' => 30000.0, 'benefits' => 0.0, 'de_minimis' => 0.0, 'contributions' => 0.0, 'taxable' => 30000.0, 'withheld' => 1375.05, 'refund' => 0.0, 'year_end' => false],
            ['month' => 11, 'pay' => 30000.0, 'benefits' => 0.0, 'de_minimis' => 0.0, 'contributions' => 0.0, 'taxable' => 30000.0, 'withheld' => 1375.05, 'refund' => 0.0, 'year_end' => false],
            ['month' => 12, 'pay' => 30000.0, 'benefits' => 7500.0, 'de_minimis' => 0.0, 'contributions' => 0.0, 'taxable' => 30000.0, 'withheld' => 0.0, 'refund' => 2750.1, 'year_end' => true],
        ]);
});

test('a full year settles to the tax on the year’s pay, and leaving early refunds the excess', function () {
    $payroll = app(Payroll::class);
    $full = $payroll->employeeYear(joy(['monthly_pay' => 50_000, 'started_on' => '2025-06-01']), 2026);
    $left = $payroll->employeeYear(joy(['monthly_pay' => 50_000, 'started_on' => '2026-01-01', 'ended_on' => '2026-06-15']), 2026);

    expect($full)->toMatchArray(['pay' => 650000.0, 'taxable' => 600000.0, 'tax' => 62500.0, 'withheld' => 62500.0, 'refund' => 0.0])
        ->and($full['months'][0]['withheld'])->toBe(5208.4)
        ->and($full['months'][11]['withheld'])->toBe(5207.6)
        ->and($left)->toMatchArray(['pay' => 325000.0, 'taxable' => 300000.0, 'tax' => 7500.0, 'withheld' => 26042.0, 'refund' => 18542.0])
        ->and(end($left['months']))->toMatchArray(['month' => 6, 'withheld' => 0.0, 'year_end' => true]);
});

test('the 1601-C months are due on the 10th, December’s on January 15, and show on the calendar', function () {
    joy();
    $months = app(Payroll::class)->year(2026);

    expect(array_column($months, 'month'))->toBe([10, 11, 12])
        ->and($months[0])->toMatchArray(['employees' => 1, 'compensation' => 30000.0, 'withheld' => 1375.05, 'due_date' => '2026-11-10', 'status' => 'due'])
        ->and($months[2])->toMatchArray(['compensation' => 37500.0, 'benefits' => 7500.0, 'withheld' => 0.0, 'refund' => 2750.1, 'status' => 'open', 'due_date' => '2027-01-15']);

    $profile = TaxProfile::current();
    $deadlines = array_values(array_filter(
        app(TaxObligations::class)->deadlines($profile, app(TaxCalculationService::class)->forYear($profile, 2026)),
        fn (array $d) => str_contains($d['title'], '1601-C'),
    ));

    expect(array_column($deadlines, 'title'))->toBe(['October 1601-C · tax on salaries', 'November 1601-C · tax on salaries', 'December 1601-C · tax on salaries'])
        ->and($deadlines[0])->toMatchArray(['date' => '2026-11-10', 'amount' => 1375.05, 'href' => '/payroll/2026/10']);

    // The BIR's copies of the 2316s, scanned, by February 28 (2027-02-28 is a Sunday).
    $copies = collect(app(TaxObligations::class)->deadlines($profile, app(TaxCalculationService::class)->forYear($profile, 2026)))
        ->firstWhere('title', '2026 2316s to the BIR · scanned copies');
    expect($copies)->toMatchArray(['date' => '2027-03-01', 'kind' => 'reminder']);
});

test('the 1601-C is filled from the month’s payroll and marked remitted', function () {
    joy();
    $october = app(Payroll::class)->year(2026)[0];

    // Joy's ₱90,000 for the year is under ₱250,000: taxable, but not subject to withholding (item 23).
    expect(app(Form1601CItems::class)->values(TaxProfile::current(), 2026, $october))->toMatchArray([
        '1' => '102026', '2.no' => true, '3.yes' => true, '11.private' => true, '7' => '028',
        '14' => 30000.0, '15' => 0.0, '19' => 0.0, '21' => 0.0, '22' => 30000.0, '23' => 30000.0, '24' => 0.0,
        '25' => 1375.05, '27' => 1375.05, '31' => 1375.05, '35' => null, '36' => 1375.05,
    ]);

    $this->get('/payroll/2026/10')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('payroll/month')->where('remittance.withheld', 1375.05)->where('employees.0.name', 'Joy Ramos'));
    $response = $this->get('/payroll/2026/10/pdf')->assertOk();
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
    $this->get('/payroll/2026/12/pdf')->assertNotFound();
    $this->get('/payroll/2026/9')->assertNotFound();

    $this->post('/filings', [
        'year' => 2026, 'quarter' => 4, 'month' => 10, 'form' => '1601-C', 'filed_on' => '2026-11-05',
        'reference_number' => '', 'paid' => true, 'amount_paid' => '1375', 'paid_on' => '2026-11-05', 'payment_channel' => 'online',
    ])->assertSessionHasNoErrors();

    expect(Filing::query()->where('form', '1601-C')->sole())->month->toBe(10)->snapshot->toBeNull()
        ->and(app(Payroll::class)->year(2026)[0]['status'])->toBe('filed');

    // December is a quarter's last month: fine for the 1601-C, unlike the 0619-E.
    $this->travelTo(CarbonImmutable::parse('2027-01-05 10:00'));
    $this->post('/filings', [
        'year' => 2026, 'quarter' => 4, 'month' => 12, 'form' => '1601-C', 'filed_on' => '2027-01-05', 'paid' => false,
    ])->assertSessionHasNoErrors();
    $this->post('/filings', ['year' => 2026, 'quarter' => 3, 'month' => 12, 'form' => '1601-C', 'filed_on' => '2027-01-05', 'paid' => false])
        ->assertSessionHasErrors('month');
});

test('employees are added, edited and removed, and kept in backups', function () {
    $this->post('/payroll/employees', ['first_name' => 'Joy', 'last_name' => 'Ramos', 'monthly_pay' => '0', 'started_on' => '2026-10-01'])
        ->assertSessionHasErrors('monthly_pay');
    $this->post('/payroll/employees', ['first_name' => 'Joy', 'last_name' => 'Ramos', 'monthly_pay' => '30000', 'started_on' => '2026-10-01', 'ended_on' => '2026-09-01'])
        ->assertSessionHasErrors('ended_on');

    $this->post('/payroll/employees', ['first_name' => 'Joy', 'last_name' => 'Ramos', 'monthly_pay' => '30000', 'started_on' => '2026-10-01'])
        ->assertSessionHasNoErrors();
    $employee = Employee::query()->sole();

    $this->get("/payroll/employees/{$employee->id}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('payroll/employee')
        ->where('payday', ['pay' => 30000, 'de_minimis' => 0, 'contributions' => 0, 'taxable' => 30000, 'withheld' => 1375.05, 'net' => 28624.95])
        ->where('yearEnd.refund', 2750.1)
        ->where('yearEnd.refund_by', '2027-01-25')
        ->where('nextRemittance.month', 10)
        ->where('annualDue', '2027-02-01'));

    $this->put("/payroll/employees/{$employee->id}", ['first_name' => 'Joy', 'last_name' => 'Ramos', 'monthly_pay' => '35000', 'started_on' => '2026-10-01'])
        ->assertSessionHasNoErrors();
    expect($employee->fresh()->monthly_pay)->toBe('35000.00')
        ->and(app(LocalData::class)->rows()['employees'])->toHaveCount(1);

    $this->get('/payroll')->assertInertia(fn (Assert $page) => $page->has('employees', 1)->has('months', 3));

    $this->delete("/payroll/employees/{$employee->id}")->assertRedirect('/payroll');
    expect(Employee::query()->count())->toBe(0);
});
