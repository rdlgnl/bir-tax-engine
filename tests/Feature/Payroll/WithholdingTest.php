<?php

use App\Payroll\Models\Employee;
use App\Payroll\Services\Payroll;
use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\Items\Form1601CItems;
use App\Returns\Forms\Items\Form2316Items;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-05 10:00'));
    TaxProfile::factory()->create();
});

function staffer(array $overrides = []): Employee
{
    return Employee::query()->create([
        'first_name' => 'Ana', 'last_name' => 'Cruz', 'tin' => '456-789-012-000',
        'monthly_pay' => 40_000, 'started_on' => '2025-03-01', ...$overrides,
    ]);
}

test('contributions and de minimis benefits are left out of taxable pay, on the 1601-C too', function () {
    $ana = staffer(['contributions' => 2_000, 'de_minimis' => 2_000]);
    $year = app(Payroll::class)->employeeYear($ana, 2026);

    // ₱38,000 taxable a month: ₱1,875 + 20% over ₱33,333. The 13th month, ₱40,000, is under ₱90,000.
    expect($year['months'][0])->toMatchArray(['taxable' => 38000.0, 'withheld' => 2808.4])
        ->and($year)->toMatchArray(['pay' => 544000.0, 'taxable' => 456000.0, 'tax' => 33700.0, 'withheld' => 33700.0, 'refund' => 0.0]);

    $march = app(Payroll::class)->year(2026)[2];
    expect(app(Form1601CItems::class)->values(TaxProfile::current(), 2026, $march))->toMatchArray([
        '14' => 42000.0, '17' => 0.0, '18' => 2000.0, '19' => 2000.0, '21' => 4000.0, '22' => 38000.0, '23' => 0.0, '24' => 38000.0, '25' => 2808.4,
    ]);

    expect(app(Form2316Items::class)->values(TaxProfile::current(), $ana, 2026, $year))->toMatchArray([
        '19' => 544000.0, '20' => 88000.0, '21' => 456000.0, '29' => 0.0, '34' => 40000.0, '35' => 24000.0, '36' => 24000.0,
        '38' => 88000.0, '39' => 456000.0, '48' => 0.0, '52' => 456000.0,
    ]);
});

test('the 13th month above ₱90,000 is taxed with the year-end adjustment', function () {
    $boss = staffer(['monthly_pay' => 120_000]);
    $year = app(Payroll::class)->employeeYear($boss, 2026);

    expect($year)->toMatchArray(['benefits' => 120000.0, 'benefits_exempt' => 90000.0, 'taxable' => 1470000.0])
        ->and(end($year['months']))->toMatchArray(['benefits' => 120000.0, 'taxable' => 150000.0, 'year_end' => true])
        ->and($year['withheld'])->toBe($year['tax'])
        ->and(app(Form2316Items::class)->values(TaxProfile::current(), $boss, 2026, $year))
        ->toMatchArray(['34' => 90000.0, '48' => 30000.0, '52' => 1470000.0]);
});

test('a minimum wage earner’s basic pay isn’t withheld on, and their 2316 says so', function () {
    $lito = staffer(['first_name' => 'Lito', 'monthly_pay' => 15_000, 'contributions' => 800, 'minimum_wage' => true]);
    $year = app(Payroll::class)->employeeYear($lito, 2026);

    expect($year)->toMatchArray(['minimum_wage' => true, 'taxable' => 0.0, 'tax' => 0.0, 'withheld' => 0.0])
        ->and(app(Payroll::class)->year(2026)[0])->toMatchArray(['minimum_wage' => 14200.0, 'contributions' => 800.0, 'taxable' => 0.0, 'not_withheld' => 0.0])
        ->and(app(Form2316Items::class)->values(TaxProfile::current(), $lito, 2026, $year))->toMatchArray([
            '10' => '15,000.00', '11' => true, '29' => 170400.0, '34' => 15000.0, '36' => 9600.0, '21' => 0.0, '39' => 0.0,
        ]);
});

test('someone who joined from another employer this year is withheld on the cumulative average', function () {
    // ₱120,000 earned January to June elsewhere, nothing withheld; ₱50,000 a month here from July.
    $ben = staffer(['first_name' => 'Ben', 'monthly_pay' => 50_000, 'started_on' => '2026-07-01', 'previous_pay' => 120_000, 'previous_withheld' => 0]);
    $year = app(Payroll::class)->employeeYear($ben, 2026);

    // July: the table on ₱170,000 ÷ 7, times 7. August: on ₱220,000 ÷ 8, times 8, less July's.
    expect($year['method'])->toBe('cumulative')
        ->and($year['months'][0]['withheld'])->toBe(3625.37)
        ->and($year['months'][1]['withheld'])->toBe(4375.03)
        // The year's tax is on both employers' pay: ₱420,000.
        ->and($year)->toMatchArray(['taxable' => 300000.0, 'previous_pay' => 120000.0, 'tax' => 26500.0, 'withheld' => 26500.0])
        ->and(app(Form2316Items::class)->values(TaxProfile::current(), $ben, 2026, $year))->toMatchArray([
            '21' => 300000.0, '22' => 120000.0, '23' => 420000.0, '24' => 26500.0, '25A' => 26500.0, '25B' => 0.0, '26' => 26500.0,
        ]);

    // The next year it's the table again.
    expect(app(Payroll::class)->employeeYear($ben, 2027)['method'])->toBe('table');
});

test('the employee form saves what isn’t taxed and lists the de minimis limits', function () {
    $this->get('/payroll/employees/new')->assertInertia(fn (Assert $page) => $page
        ->where('deMinimis.0', ['label' => 'Rice subsidy', 'amount' => 2500, 'per' => 'month']));

    $this->post('/payroll/employees', ['first_name' => 'Ana', 'last_name' => 'Cruz', 'monthly_pay' => '40000', 'contributions' => '40000', 'started_on' => '2026-01-01'])
        ->assertSessionHasErrors(['contributions' => 'Their share of contributions should be less than their pay.']);

    $this->post('/payroll/employees', [
        'first_name' => 'Ana', 'last_name' => 'Cruz', 'monthly_pay' => '40000', 'contributions' => '2000', 'de_minimis' => '',
        'minimum_wage' => false, 'started_on' => '2026-01-01', 'previous_pay' => '', 'previous_withheld' => '',
    ])->assertSessionHasNoErrors();

    expect(Employee::query()->sole())
        ->contributions->toBe('2000.00')->de_minimis->toBe('0.00')->minimum_wage->toBeFalse()->previous_pay->toBeNull();

    $this->get('/payroll/employees/'.Employee::query()->sole()->id)->assertInertia(fn (Assert $page) => $page
        ->where('payday.taxable', 38000)
        ->where('payday.withheld', 2808.4)
        ->where('payday.net', 35191.6));
});
