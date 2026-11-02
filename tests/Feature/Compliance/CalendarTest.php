<?php

use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
});

test('November shows Q3 moved off the Sunday, with holidays', function () {
    $this->get('/calendar?month=2026-11')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('calendar/index')
        ->where('month', '2026-11')
        ->where('deadlines.0.title', 'Q3 1701Q · file + pay')
        ->where('deadlines.0.date', '2026-11-16')
        ->where('deadlines.0.statutory_date', '2026-11-15')
        ->where('deadlines.0.amount', 14000)
        ->where('holidays', [
            ['date' => '2026-11-01', 'name' => "All Saints' Day"],
            ['date' => '2026-11-02', 'name' => "All Souls' Day"],
            ['date' => '2026-11-30', 'name' => 'Bonifacio Day'],
        ])
        ->where('comingUp.0.date', '2026-11-16'));
});

test('filed returns stay on the calendar as filed', function () {
    $this->get('/calendar?month=2026-08')->assertInertia(fn (Assert $page) => $page
        ->where('deadlines.0.title', 'Q2 1701Q · file + pay')
        ->where('deadlines.0.status', 'filed'));
});

test('the next year shows last year’s annual return', function () {
    $this->get('/calendar?month=2027-04')->assertInertia(fn (Assert $page) => $page
        ->where('deadlines.0.title', '2026 annual return (1701-MS)')
        ->where('deadlines.0.date', '2027-04-15'));
});

test('the month must be a real month', function () {
    $this->get('/calendar?month=2026-13')->assertSessionHasErrors('month');
});

test('deadlines export as an .ics file with reminders', function () {
    $response = $this->get('/calendar/export')->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('text/calendar')
        ->and($response->headers->get('Content-Disposition'))->toContain('bir-deadlines.ics');

    $ics = $response->getContent();
    expect($ics)->toStartWith("BEGIN:VCALENDAR\r\n")
        ->toContain("DTSTART;VALUE=DATE:20261116\r\n")
        ->toContain('SUMMARY:BIR: Q3 1701Q · file + pay')
        ->toContain('DESCRIPTION:Amount due: PHP 14\,000.00')
        ->toContain('TRIGGER:-P7D')
        // Q2 2026 is filed, so its Aug 17 deadline is left out.
        ->not->toContain('DTSTART;VALUE=DATE:20260817');
});
