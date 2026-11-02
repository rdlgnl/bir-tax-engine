<?php

namespace App\Compliance\Controllers;

use App\Compliance\Models\HolidayChange;
use App\Compliance\Requests\SaveHolidayRequest;
use App\Http\Controllers\Controller;
use App\Tax\Services\FilingCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * The taxpayer's changes to a year's holidays, for when a proclamation
 * adds, moves or names a day differently from the list the app ships.
 * Deadlines follow the changed list.
 */
class HolidayController extends Controller
{
    public function __construct(private FilingCalendar $calendar) {}

    /**
     * A day added as a holiday, or a listed one renamed.
     */
    public function store(SaveHolidayRequest $request): RedirectResponse
    {
        $date = CarbonImmutable::parse($request->validated('date'));
        $name = trim((string) $request->validated('name'));

        // The same as the list already has: nothing to keep.
        if (($this->calendar->shipped($date->year)[$date->toDateString()] ?? null) === $name) {
            HolidayChange::query()->whereDate('date', $date)->first()?->delete();
        } else {
            HolidayChange::query()->updateOrCreate(['date' => $date->toDateString()], ['name' => $name, 'holiday' => true]);
        }

        return Inertia::flash('toast', "{$date->format('M j, Y')} is a holiday.")->back();
    }

    /**
     * A day that isn't a holiday after all: a listed one is turned off, one
     * the taxpayer added is removed.
     */
    public function off(string $date): RedirectResponse
    {
        $day = CarbonImmutable::parse($date);

        if (array_key_exists($day->toDateString(), $this->calendar->shipped($day->year))) {
            HolidayChange::query()->updateOrCreate(['date' => $day->toDateString()], ['name' => null, 'holiday' => false]);
        } else {
            HolidayChange::query()->whereDate('date', $day)->first()?->delete();
        }

        return Inertia::flash('toast', "{$day->format('M j, Y')} isn't a holiday.")->back();
    }

    /**
     * A day back as the app ships it.
     */
    public function undo(string $date): RedirectResponse
    {
        $day = CarbonImmutable::parse($date);
        HolidayChange::query()->whereDate('date', $day)->first()?->delete();

        return Inertia::flash('toast', "{$day->format('M j, Y')} is back as listed.")->back();
    }

    /**
     * A year's holidays back as the app ships them.
     */
    public function reset(int $year): RedirectResponse
    {
        HolidayChange::query()->whereYear('date', (string) $year)->get()->each->delete();

        return Inertia::flash('toast', "{$year}'s holidays are back as listed.")->back();
    }
}
