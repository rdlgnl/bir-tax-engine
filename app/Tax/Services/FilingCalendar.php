<?php

namespace App\Tax\Services;

use App\Compliance\Models\HolidayChange;
use App\Profiles\Models\TaxProfile;
use App\Tax\Calculation\YearTaxpayer;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Enums\Regime;
use App\Tax\Enums\TaxpayerClassification;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * BIR due dates from the rule pack, moved off weekends and holidays.
 *
 * @phpstan-type HolidayRow array{date: string, name: string, source: 'proclaimed'|'estimated'|'yours', changed: bool, off: bool}
 */
class FilingCalendar
{
    /** @var array<int, array<string, string>> each year's holidays, once worked out */
    private array $years = [];

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

    public function vatDue(int $year, int $quarter): CarbonImmutable
    {
        $statutory = CarbonImmutable::create($year, $quarter * 3)
            ->endOfMonth()
            ->startOfDay()
            ->addDays(config('tax.deadlines.vat_days_after_quarter'));

        return $this->nextWorkingDay($statutory);
    }

    /**
     * 0619-E: the withholding of the first two months of a quarter, by the
     * 10th of the month after.
     */
    public function withholdingMonthlyDue(int $year, int $month): CarbonImmutable
    {
        return $this->nextWorkingDay(CarbonImmutable::create($year, $month)->addMonth()->day((int) config('tax.withholding.monthly_due_day')));
    }

    /**
     * 1601-C: the month's tax withheld on salaries, by the 10th of the month
     * after; December's by January 15 (RR 2-98 Sec. 2.83, as amended).
     */
    public function compensationMonthlyDue(int $year, int $month): CarbonImmutable
    {
        return $month === 12
            ? $this->nextWorkingDay(CarbonImmutable::create($year + 1, 1, (int) config('tax.withholding.december_compensation_due_day')))
            : $this->withholdingMonthlyDue($year, $month);
    }

    /**
     * The employer's copies of the year's 2316s, scanned, with the list of
     * employees qualified for substituted filing: by February 28 (RR 16-2021).
     */
    public function compensationCertificatesToBirDue(int $year): CarbonImmutable
    {
        [$month, $day] = config('tax.withholding.certificates_to_bir');

        return $this->nextWorkingDay(CarbonImmutable::create($year + 1, $month, $day));
    }

    /**
     * 1601-EQ: the quarter's return, by the last day of the month after it.
     */
    public function withholdingQuarterlyDue(int $year, int $quarter): CarbonImmutable
    {
        return $this->nextWorkingDay(CarbonImmutable::create($year, $quarter * 3)->addMonthNoOverflow()->endOfMonth()->startOfDay());
    }

    /**
     * Form 2000 for a document signed on a date: 5 days after that month
     * ends, or the next working day.
     */
    public function dstDue(CarbonInterface $signedOn): CarbonImmutable
    {
        return $this->nextWorkingDay(CarbonImmutable::parse($signedOn)->endOfMonth()->startOfDay()->addDays((int) config('tax.dst.due_days_after_month')));
    }

    /**
     * The 1604-E for a year: March 1 of the next year, or the next working day.
     */
    public function withholdingAnnualDue(int $year): CarbonImmutable
    {
        return $this->nextWorkingDay(CarbonImmutable::create($year + 1, 3, 1));
    }

    /**
     * The second half of a year's annual income tax, when paid in two:
     * October 15 of the next year.
     */
    public function secondInstallmentDue(int $year): CarbonImmutable
    {
        [$month, $day] = config('tax.deadlines.second_installment');

        return $this->nextWorkingDay(CarbonImmutable::create($year + 1, $month, $day));
    }

    public function swornDeclarationDue(int $year): CarbonImmutable
    {
        [$month, $day] = config('tax.deadlines.sworn_declaration');

        return $this->nextWorkingDay(CarbonImmutable::create($year, $month, $day));
    }

    /**
     * The return for a quarter, as YearTaxpayer::incomeTaxForm chooses it
     * for the profile as it stands.
     */
    public function incomeTaxForm(TaxProfile $profile, int $quarter, TaxpayerClassification $class = TaxpayerClassification::Micro): string
    {
        return YearTaxpayer::of($profile)->incomeTaxForm($quarter, $class);
    }

    /**
     * The other annual form the BIR accepts: in place of the 1701-MS, the
     * older 1701A on 8% or OSD and the 1701 on itemized deductions; with a
     * salary, the shorter 1701-MS for micro taxpayers in place of the 1701.
     */
    public function alternativeAnnualForm(TaxProfile $profile, TaxpayerClassification $class = TaxpayerClassification::Micro): ?string
    {
        if (! $class->microOrSmall()) {
            return null;
        }

        if (! $profile->isPurelySelfEmployed()) {
            return '1701-MS';
        }

        return $profile->regime === Regime::EightPercent || $profile->deduction_method === DeductionMethod::Osd ? '1701A' : '1701';
    }

    public function nextWorkingDay(CarbonImmutable $date): CarbonImmutable
    {
        while ($date->isWeekend() || $this->holidayName($date) !== null) {
            $date = $date->addDay();
        }

        return $date;
    }

    /**
     * A year's holidays: the proclaimed list in the rule pack, or, for a
     * year it doesn't have yet, the estimate from the usual days; then the
     * taxpayer's own changes to them.
     *
     * @return array<string, string> holiday names keyed by Y-m-d
     */
    public function holidaysIn(int $year): array
    {
        if (! isset($this->years[$year])) {
            $days = $this->shipped($year);

            foreach ($this->changes($year) as $change) {
                $date = $change->date->toDateString();

                if ($change->holiday) {
                    $days[$date] = (string) $change->name;
                } else {
                    unset($days[$date]);
                }
            }

            ksort($days);
            $this->years[$year] = $days;
        }

        return $this->years[$year];
    }

    /**
     * The year's holidays as the app ships them, before the taxpayer's
     * changes: the rule pack's list, or the estimate.
     *
     * @return array<string, string>
     */
    public function shipped(int $year): array
    {
        return $this->knowsHolidays($year) ? $this->proclaimed($year) : $this->usualHolidays($year);
    }

    /**
     * A year's holidays for the calendar's list: each day with where it
     * comes from, whether the taxpayer changed it, and the listed days they
     * turned off.
     *
     * @return list<HolidayRow>
     */
    public function holidayList(int $year): array
    {
        $shipped = $this->shipped($year);
        $source = $this->knowsHolidays($year) ? 'proclaimed' : 'estimated';
        $rows = [];

        foreach ($shipped as $date => $name) {
            $rows[$date] = ['date' => $date, 'name' => $name, 'source' => $source, 'changed' => false, 'off' => false];
        }

        foreach ($this->changes($year) as $change) {
            $date = $change->date->toDateString();
            $listed = isset($shipped[$date]);

            if ($change->holiday) {
                $rows[$date] = ['date' => $date, 'name' => (string) $change->name, 'source' => $listed ? $source : 'yours', 'changed' => true, 'off' => false];
            } elseif ($listed) {
                $rows[$date] = ['date' => $date, 'name' => $shipped[$date], 'source' => $source, 'changed' => true, 'off' => true];
            }
        }

        ksort($rows);

        return array_values($rows);
    }

    /**
     * Whether the rule pack lists a year's proclaimed holidays. Without
     * them, the year's holidays are estimated.
     */
    public function knowsHolidays(int $year): bool
    {
        return $this->proclaimed($year) !== [];
    }

    public function holidayName(CarbonImmutable $date): ?string
    {
        return $this->holidaysIn($date->year)[$date->toDateString()] ?? null;
    }

    /**
     * @return Collection<int, HolidayChange>
     */
    private function changes(int $year): Collection
    {
        return HolidayChange::query()->whereYear('date', (string) $year)->orderBy('date')->get();
    }

    /**
     * @return array<string, string>
     */
    private function proclaimed(int $year): array
    {
        /** @var array<string, string> $holidays */
        $holidays = config('tax.holidays');

        return array_filter($holidays, fn (string $date) => str_starts_with($date, "{$year}-"), ARRAY_FILTER_USE_KEY);
    }

    /**
     * The holidays every year keeps, on this year's dates.
     *
     * @return array<string, string>
     */
    private function usualHolidays(int $year): array
    {
        /** @var array{fixed: array<string, string>, before_easter: array<int, string>, last_monday_of_august: string} $usual */
        $usual = config('tax.usual_holidays');
        $easter = $this->easter($year);
        $days = [];

        foreach ($usual['fixed'] as $monthDay => $name) {
            $days["{$year}-{$monthDay}"] = $name;
        }

        foreach ($usual['before_easter'] as $before => $name) {
            $days[$easter->subDays($before)->toDateString()] = $name;
        }

        $days[CarbonImmutable::create($year, 8)->lastOfMonth(CarbonInterface::MONDAY)->toDateString()] = $usual['last_monday_of_august'];
        ksort($days);

        return $days;
    }

    /**
     * Easter Sunday in the Gregorian calendar (the anonymous algorithm).
     */
    private function easter(int $year): CarbonImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return CarbonImmutable::create($year, $month, $day);
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
