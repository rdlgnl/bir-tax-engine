<?php

namespace App\Compliance\Controllers;

use App\Compliance\Requests\ShowCalendarRequest;
use App\Compliance\Services\TaxObligations;
use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxProfile;
use App\Tax\Services\FilingCalendar;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @phpstan-import-type Deadline from TaxObligations
 */
class CalendarController extends Controller
{
    public function __construct(
        private TaxCalculationService $engine,
        private TaxObligations $obligations,
        private FilingCalendar $calendar,
    ) {}

    public function index(ShowCalendarRequest $request): InertiaResponse
    {
        $today = CarbonImmutable::now();
        $month = CarbonImmutable::createFromFormat('Y-m-d', ($request->validated('month') ?? $today->format('Y-m')).'-01')->startOfDay();
        $first = $month->toDateString();
        $last = $month->endOfMonth()->toDateString();

        $inMonth = fn (string $date) => $date >= $first && $date <= $last;

        return Inertia::render('calendar/index', [
            'month' => $month->format('Y-m'),
            'today' => $today->toDateString(),
            // A month can hold last year's annual return as well as this year's.
            'deadlines' => array_values(array_filter(
                $this->deadlines([$month->year - 1, $month->year], $today),
                fn (array $d) => $inMonth($d['date']) || $inMonth($d['statutory_date']),
            )),
            'holidays' => array_values(array_map(
                fn (string $date) => ['date' => $date, 'name' => $this->calendar->holidays()[$date]],
                array_filter(array_keys($this->calendar->holidays()), $inMonth),
            )),
            'comingUp' => $this->obligations->upcoming(
                TaxProfile::current(),
                $this->engine->forYear(TaxProfile::current(), $today->year, $today),
                $today,
                limit: 5,
            ),
        ]);
    }

    /**
     * The next twelve months of deadlines as an .ics file, for any calendar app.
     */
    public function export(): Response
    {
        $today = CarbonImmutable::now();
        $until = $today->addYear()->toDateString();

        $events = [];

        foreach ($this->deadlines([$today->year - 1, $today->year, $today->year + 1], $today) as $deadline) {
            if ($deadline['status'] !== 'filed' && $deadline['date'] >= $today->toDateString() && $deadline['date'] <= $until) {
                $events = [...$events, ...$this->event($deadline, $today)];
            }
        }

        $calendar = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//BuwisBuddy//BIR deadlines//EN',
            'CALSCALE:GREGORIAN',
            'X-WR-CALNAME:BIR deadlines',
            ...$events,
            'END:VCALENDAR',
        ])."\r\n";

        return response($calendar, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="bir-deadlines.ics"',
        ]);
    }

    /**
     * Deadlines from the given tax years, skipping years with nothing recorded.
     *
     * @param  list<int>  $years
     * @return list<Deadline>
     */
    private function deadlines(array $years, CarbonImmutable $today): array
    {
        $profile = TaxProfile::current();
        $first = Transaction::query()->min('occurred_on');
        $firstYear = min($first ? CarbonImmutable::parse($first)->year : $today->year, $today->year);
        $deadlines = [];

        foreach ($years as $year) {
            if ($year < $firstYear) {
                continue;
            }

            $result = $this->engine->forYear($profile, $year, $today);

            foreach ($this->obligations->deadlines($profile, $result, includeFiled: true) as $deadline) {
                // Neighbouring years can both name the same date (e.g. Q1's method lock).
                $deadlines[$deadline['date'].$deadline['title']] = $deadline;
            }
        }

        $deadlines = array_values($deadlines);
        usort($deadlines, fn (array $a, array $b) => strcmp($a['date'], $b['date']));

        return $deadlines;
    }

    /**
     * @param  Deadline  $deadline
     * @return list<string>
     */
    private function event(array $deadline, CarbonImmutable $today): array
    {
        $date = CarbonImmutable::parse($deadline['date']);
        $escape = fn (string $text) => str_replace(['\\', ';', ',', "\n"], ['\\\\', '\;', '\,', '\n'], $text);
        $description = $deadline['amount'] !== null
            ? 'Amount due: PHP '.number_format($deadline['amount'], 2)
            : (string) $deadline['note'];

        return [
            'BEGIN:VEVENT',
            'UID:'.$date->format('Ymd').'-'.Str::slug($deadline['title']).'@buwisbuddy',
            'DTSTAMP:'.$today->utc()->format('Ymd\THis\Z'),
            'DTSTART;VALUE=DATE:'.$date->format('Ymd'),
            'DTEND;VALUE=DATE:'.$date->addDay()->format('Ymd'),
            'SUMMARY:'.$escape('BIR: '.$deadline['title']),
            'DESCRIPTION:'.$escape($description),
            'BEGIN:VALARM',
            'TRIGGER:-P7D',
            'ACTION:DISPLAY',
            'DESCRIPTION:'.$escape($deadline['title'].' in 7 days'),
            'END:VALARM',
            'END:VEVENT',
        ];
    }
}
