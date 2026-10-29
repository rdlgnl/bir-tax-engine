<?php

namespace App\Tax\Services;

use App\Profiles\Models\TaxProfile;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Enums\Regime;
use Carbon\CarbonImmutable;

/**
 * BIR due dates from the rule pack, moved off weekends and holidays.
 */
class FilingCalendar
{
    /**
     * The date the law names for an income tax return. Quarter 4 is the
     * annual return, due the following year.
     */
    public function incomeTaxStatutoryDate(int $year, int $quarter): CarbonImmutable
    {
        if ($quarter === 4) {
            [$month, $day] = config('tax.deadlines.income_tax_annual');

            return CarbonImmutable::create($year + 1, $month, $day);
        }

        [$month, $day] = config("tax.deadlines.income_tax_quarterly.{$quarter}");

        return CarbonImmutable::create($year, $month, $day);
    }

    public function incomeTaxDue(int $year, int $quarter): CarbonImmutable
    {
        return $this->nextWorkingDay($this->incomeTaxStatutoryDate($year, $quarter));
    }

    public function percentageTaxDue(int $year, int $quarter): CarbonImmutable
    {
        $statutory = CarbonImmutable::create($year, $quarter * 3)
            ->endOfMonth()
            ->startOfDay()
            ->addDays(config('tax.deadlines.percentage_tax_days_after_quarter'));

        return $this->nextWorkingDay($statutory);
    }

    public function swornDeclarationDue(int $year): CarbonImmutable
    {
        [$month, $day] = config('tax.deadlines.sworn_declaration');

        return $this->nextWorkingDay(CarbonImmutable::create($year, $month, $day));
    }

    public function incomeTaxForm(TaxProfile $profile, int $quarter): string
    {
        if ($quarter < 4) {
            return '1701Q';
        }

        $simpleAnnual = $profile->isPurelySelfEmployed()
            && ($profile->regime === Regime::EightPercent || $profile->deduction_method === DeductionMethod::Osd);

        return $simpleAnnual ? '1701A' : '1701';
    }

    public function nextWorkingDay(CarbonImmutable $date): CarbonImmutable
    {
        $holidays = $this->holidays();

        while ($date->isWeekend() || array_key_exists($date->toDateString(), $holidays)) {
            $date = $date->addDay();
        }

        return $date;
    }

    /**
     * @return array<string, string> holiday names keyed by Y-m-d
     */
    public function holidays(): array
    {
        return config('tax.holidays');
    }

    public function holidayName(CarbonImmutable $date): ?string
    {
        return $this->holidays()[$date->toDateString()] ?? null;
    }

    /**
     * Why a deadline moved, or null when it didn't.
     */
    public function movedReason(CarbonImmutable $statutory, CarbonImmutable $due): ?string
    {
        if ($statutory->equalTo($due)) {
            return null;
        }

        return $statutory->isWeekend()
            ? "{$statutory->format('M j')} is a {$statutory->format('l')}"
            : "{$statutory->format('M j')} is {$this->holidayName($statutory)}";
    }
}
