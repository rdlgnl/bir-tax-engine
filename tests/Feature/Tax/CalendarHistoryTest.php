<?php

use App\Compliance\Services\TaxObligations;
use App\Profiles\Models\TaxProfile;
use App\Returns\Models\Filing;
use App\Tax\Services\FilingCalendar;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

test('past years’ holidays move their deadlines, and a year without a list says so', function () {
    $calendar = app(FilingCalendar::class);

    // National Heroes Day 2018, Bonifacio Day 2023 (moved to Monday, Nov 27), and 2025's elections.
    expect($calendar->nextWorkingDay(CarbonImmutable::parse('2018-08-27'))->toDateString())->toBe('2018-08-28')
        ->and($calendar->nextWorkingDay(CarbonImmutable::parse('2023-11-27'))->toDateString())->toBe('2023-11-28')
        ->and($calendar->nextWorkingDay(CarbonImmutable::parse('2025-05-12'))->toDateString())->toBe('2025-05-13')
        ->and($calendar->knowsHolidays(2018))->toBeTrue()
        ->and($calendar->knowsHolidays(2028))->toBeFalse();

    $this->travelTo(CarbonImmutable::parse('2026-11-05 10:00'));
    TaxProfile::factory()->create();

    $this->get('/calendar')->assertInertia(fn (Assert $page) => $page->where('holidaysKnown', true));
    $this->get('/calendar?month=2028-01')->assertInertia(fn (Assert $page) => $page->where('holidaysKnown', false));
});

test('a year without a proclamation uses the holidays every year keeps', function () {
    $calendar = app(FilingCalendar::class);

    // Easter 2028 is April 16: Maundy Thursday to Black Saturday, then Sunday.
    expect($calendar->nextWorkingDay(CarbonImmutable::parse('2028-04-13'))->toDateString())->toBe('2028-04-17')
        // National Heroes Day, the last Monday of August.
        ->and($calendar->nextWorkingDay(CarbonImmutable::parse('2028-08-28'))->toDateString())->toBe('2028-08-29')
        ->and($calendar->holidayName(CarbonImmutable::parse('2028-06-12')))->toBe('Independence Day')
        // Days some years moved aren't assumed, so a deadline is never late.
        ->and($calendar->holidayName(CarbonImmutable::parse('2028-11-30')))->toBeNull()
        ->and($calendar->holidaysIn(2028))->toHaveCount(10)
        // A proclaimed year keeps its own list.
        ->and($calendar->holidaysIn(2023))->toHaveKey('2023-04-10');

    $this->travelTo(CarbonImmutable::parse('2026-11-05 10:00'));
    TaxProfile::factory()->create();

    $this->get('/calendar?month=2028-04')->assertInertia(fn (Assert $page) => $page
        ->where('holidays.0', ['date' => '2028-04-13', 'name' => 'Maundy Thursday'])
        ->has('holidays', 3));
});

test('annual tax over ₱2,000 can be paid in two, and the second half goes on the calendar', function () {
    $this->travelTo(CarbonImmutable::parse('2026-03-01 10:00'));
    $profile = TaxProfile::factory()->create();
    // 8% of ₱250,000 over the reduction: ₱20,000 for the year, all of it on the annual return.
    Transaction::factory()->on('2025-12-10')->create(['amount' => 500_000]);

    $this->get('/file/2025/4/1701-MS')->assertInertia(fn (Assert $page) => $page
        ->where('secondInstallment', ['amount' => 10000, 'due' => '2026-10-15']));

    Filing::factory()->create(['year' => 2025, 'quarter' => 4, 'form' => '1701-MS', 'filed_on' => '2026-04-15', 'paid_on' => '2026-04-15', 'amount_paid' => 10_000]);
    $this->travelTo(CarbonImmutable::parse('2026-05-01 10:00'));

    $second = collect(app(TaxObligations::class)->deadlines($profile, app(TaxCalculationService::class)->forYear($profile, 2025)))
        ->firstWhere('title', '2025 income tax · second half');

    expect($second)->toMatchArray(['date' => '2026-10-15', 'amount' => 10000.0, 'status' => 'due']);
});
