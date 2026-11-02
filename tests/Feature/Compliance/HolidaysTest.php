<?php

use App\Compliance\Models\HolidayChange;
use App\Data\Services\LocalData;
use App\Profiles\Models\TaxProfile;
use App\Tax\Services\FilingCalendar;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-05 10:00'));
    TaxProfile::factory()->create();
});

function workingDay(string $date): string
{
    return app(FilingCalendar::class)->nextWorkingDay(CarbonImmutable::parse($date))->toDateString();
}

test('a holiday the taxpayer adds moves the deadlines on it', function () {
    // Bonifacio Day isn't assumed for a year without a proclamation.
    expect(workingDay('2028-11-30'))->toBe('2028-11-30');

    $this->post('/calendar/holidays', ['date' => '2028-11-30', 'name' => 'Bonifacio Day'])
        ->assertRedirect()->assertInertiaFlash('toast', 'Nov 30, 2028 is a holiday.');

    expect(workingDay('2028-11-30'))->toBe('2028-12-01')
        ->and(app(FilingCalendar::class)->holidayList(2028))->toContain(
            ['date' => '2028-11-30', 'name' => 'Bonifacio Day', 'source' => 'yours', 'changed' => true, 'off' => false],
        );
});

test('a listed day can be turned off, and undone', function () {
    // 2023's proclamation moved Araw ng Kagitingan to Monday, April 10.
    expect(workingDay('2023-04-10'))->toBe('2023-04-11');

    $this->post('/calendar/holidays/2023-04-10/off')->assertRedirect();

    expect(workingDay('2023-04-10'))->toBe('2023-04-10')
        ->and(app(FilingCalendar::class)->holidayList(2023))->toContain(
            ['date' => '2023-04-10', 'name' => 'Araw ng Kagitingan (moved)', 'source' => 'proclaimed', 'changed' => true, 'off' => true],
        );

    $this->delete('/calendar/holidays/2023-04-10')->assertRedirect();

    expect(workingDay('2023-04-10'))->toBe('2023-04-11')
        ->and(HolidayChange::query()->count())->toBe(0);
});

test('a listed holiday can be renamed; the listed name again keeps no change', function () {
    $this->post('/calendar/holidays', ['date' => '2027-12-08', 'name' => 'Feast of the Immaculate Conception']);

    expect(app(FilingCalendar::class)->holidayName(CarbonImmutable::parse('2027-12-08')))->toBe('Feast of the Immaculate Conception');

    $this->post('/calendar/holidays', ['date' => '2027-12-08', 'name' => 'Immaculate Conception']);

    expect(HolidayChange::query()->count())->toBe(0);
});

test('removing a day the taxpayer added deletes it, and a year resets', function () {
    $this->post('/calendar/holidays', ['date' => '2028-05-08', 'name' => 'Election day']);
    $this->post('/calendar/holidays/2028-05-08/off');

    expect(HolidayChange::query()->count())->toBe(0);

    $this->post('/calendar/holidays', ['date' => '2028-05-08', 'name' => 'Election day']);
    $this->post('/calendar/holidays/2028-12-25/off');
    $this->post('/calendar/holidays', ['date' => '2027-02-25', 'name' => 'EDSA Day']);
    $this->delete('/calendar/holidays/year/2028')->assertInertiaFlash('toast', "2028's holidays are back as listed.");

    expect(HolidayChange::query()->pluck('date')->map->toDateString()->all())->toBe(['2027-02-25']);
});

test('a holiday needs a day from 2018 and a name', function () {
    $this->post('/calendar/holidays', ['date' => '2017-12-25', 'name' => ''])
        ->assertSessionHasErrors(['date' => 'The app covers 2018 onwards.', 'name' => 'Name the holiday, as the proclamation does.']);
});

test('the calendar lists the year’s holidays, and backups keep the changes', function () {
    $this->post('/calendar/holidays', ['date' => '2028-05-08', 'name' => 'Election day']);

    $this->get('/calendar?month=2028-05')->assertInertia(fn (Assert $page) => $page
        ->where('holidays', [['date' => '2028-05-01', 'name' => 'Labor Day'], ['date' => '2028-05-08', 'name' => 'Election day']])
        ->where('holidayList.0', ['date' => '2028-01-01', 'name' => "New Year's Day", 'source' => 'estimated', 'changed' => false, 'off' => false])
        ->has('holidayList', 11));

    expect(LocalData::TABLES)->toContain('holiday_changes')
        ->and(app(LocalData::class)->rows()['holiday_changes'])->toHaveCount(1);
});
