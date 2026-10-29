<?php

use App\Tax\Services\FilingCalendar;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->calendar = app(FilingCalendar::class);
});

test('deadlines on a weekend move to the next working day', function (int $year, int $quarter, string $expected) {
    expect($this->calendar->incomeTaxDue($year, $quarter)->toDateString())->toBe($expected);
})->with([
    'Q1 2026, a Friday' => [2026, 1, '2026-05-15'],
    'Q2 2026, a Saturday' => [2026, 2, '2026-08-17'],
    'Q3 2026, a Sunday' => [2026, 3, '2026-11-16'],
    'Q1 2027, a Saturday' => [2027, 1, '2027-05-17'],
    'annual 2026 return' => [2026, 4, '2027-04-15'],
]);

test('holidays also move a deadline', function () {
    // Dec 30 2026 is Rizal Day, Dec 31 a special non-working day.
    $moved = $this->calendar->nextWorkingDay(CarbonImmutable::parse('2026-12-30'));

    expect($moved->toDateString())->toBe('2027-01-04');
});

test('percentage tax is due 25 days after the quarter', function () {
    // Oct 25 2026 is a Sunday.
    expect($this->calendar->percentageTaxDue(2026, 3)->toDateString())->toBe('2026-10-26');
});
