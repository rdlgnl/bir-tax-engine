<?php

use App\Corporations\Models\Corporation;
use App\Payroll\Models\Employee;
use App\Payroll\Services\Payroll;
use App\Profiles\Models\TaxProfile;
use App\Tax\Enums\EfpsGroup;
use App\Tax\Services\FilingCalendar;
use App\Withholding\Services\WithholdingAgent;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-01 10:00'));
    $this->calendar = app(FilingCalendar::class);
});

test('an eFPS filer’s 0619-E is due the 15th, not the 10th', function () {
    // February 15, 2026 is a Sunday.
    expect($this->calendar->withholdingMonthlyDue(2026, 1)->toDateString())->toBe('2026-02-10')
        ->and($this->calendar->withholdingMonthlyDue(2026, 1, EfpsGroup::A)->toDateString())->toBe('2026-02-16')
        ->and($this->calendar->withholdingMonthlyDue(2026, 1, EfpsGroup::E)->toDateString())->toBe('2026-02-16');
});

test('an eFPS filer’s 1601-C is due on its group’s day, December’s included', function (EfpsGroup $group, string $october, string $december) {
    expect($this->calendar->compensationMonthlyDue(2026, 10, $group)->toDateString())->toBe($october)
        ->and($this->calendar->compensationMonthlyDue(2025, 12, $group)->toDateString())->toBe($december);
})->with([
    // November 14 and 15, 2026 are a weekend; January 11, 2026 a Sunday.
    // December 2025's match PwC's 2026 calendar: A the 15th, B the 14th, D and E the 12th.
    'Group A' => [EfpsGroup::A, '2026-11-16', '2026-01-15'],
    'Group B' => [EfpsGroup::B, '2026-11-16', '2026-01-14'],
    'Group C' => [EfpsGroup::C, '2026-11-13', '2026-01-13'],
    'Group D' => [EfpsGroup::D, '2026-11-12', '2026-01-12'],
    'Group E' => [EfpsGroup::E, '2026-11-11', '2026-01-12'],
]);

test('eBIRForms filers keep the 10th, and December’s 1601-C on January 15', function () {
    expect($this->calendar->compensationMonthlyDue(2026, 10)->toDateString())->toBe('2026-11-10')
        ->and($this->calendar->compensationMonthlyDue(2025, 12)->toDateString())->toBe('2026-01-15');
});

test('payroll follows eFPS from the month the taxpayer names; earlier months keep their dates', function () {
    TaxProfile::factory()->create(['efps_group' => 'C', 'efps_from' => '2026-11-01']);
    Employee::query()->create([
        'first_name' => 'Joy', 'last_name' => 'Ramos', 'position' => 'Virtual assistant',
        'monthly_pay' => 30_000, 'started_on' => '2026-10-01',
    ]);

    $months = collect(app(Payroll::class)->year(2026))->pluck('due_date', 'month');

    // December 13, 2026 is a Sunday.
    expect($months->all())->toBe([10 => '2026-11-10', 11 => '2026-12-14', 12 => '2027-01-13']);
});

test('a withholding agent on eFPS has every 0619-E due the 15th', function () {
    TaxProfile::factory()->create(['withholding_agent' => true, 'efps_group' => 'A']);

    // August 15, 2026 is a Saturday.
    expect(app(WithholdingAgent::class)->year(2026)[3]['months'][0]['due_date'])->toBe('2026-08-17');
});

test('the profile says how the taxpayer files, and a blank group clears the month', function () {
    TaxProfile::factory()->create();

    $this->put('/profile/efps', ['efps_group' => 'B', 'efps_from' => '2026-03'])->assertRedirect();
    $profile = TaxProfile::current();
    expect($profile->efps_group)->toBe(EfpsGroup::B)
        ->and($profile->efps_from->toDateString())->toBe('2026-03-01')
        ->and($profile->efpsGroupFor(2026, 2))->toBeNull()
        ->and($profile->efpsGroupFor(2026, 3))->toBe(EfpsGroup::B);

    $this->get('/profile')->assertInertia(fn (Assert $page) => $page
        ->where('efps.group', 'B')
        ->where('efps.from', '2026-03')
        ->where('efps.options.1', ['value' => 'B', 'label' => 'eFPS, Group B']));

    $this->put('/profile/efps', ['efps_group' => null, 'efps_from' => '2026-03'])->assertRedirect();
    expect(TaxProfile::current()->only('efps_group', 'efps_from'))->toBe(['efps_group' => null, 'efps_from' => null]);

    $this->put('/profile/efps', ['efps_group' => 'F'])->assertSessionHasErrors('efps_group');
    $this->put('/profile/efps', ['efps_group' => 'A', 'efps_from' => 'March'])->assertSessionHasErrors('efps_from');
});

test('a corporation on eFPS is reminded of its group’s dates', function () {
    TaxProfile::factory()->create();

    $this->put('/corporation', [
        'name' => 'Bayani Creative Studio Inc.', 'operations_began' => 2022, 'total_assets' => 30_000_000,
        'deduction_method' => 'itemized', 'withholding_agent' => true, 'employees' => 5, 'efps_group' => 'D',
    ])->assertRedirect();

    expect(Corporation::current()->efps_group)->toBe(EfpsGroup::D);

    // October's 1601-C by the 12th (Group D); its 0619-E by the 15th, a Sunday.
    $this->get('/corporation')->assertInertia(fn (Assert $page) => $page
        ->where('corporation.efps_group', 'D')
        ->where('alsoDue', [
            ['title' => 'October payroll (1601-C)', 'detail' => '5 employees', 'date' => '2026-11-12'],
            ['title' => 'October withholding (0619-E)', 'detail' => 'Payments to suppliers', 'date' => '2026-11-16'],
        ]));
});
