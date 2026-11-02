<?php

namespace App\Returns\Services;

use App\Profiles\Models\TaxProfile;
use App\Returns\Models\Filing;
use App\Tax\Enums\Regime;
use App\Tax\Services\FilingCalendar;
use App\Tax\Services\TaxCalculationService;
use App\Tax\Services\VatCalculator;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;

/**
 * Every return a taxpayer has had to file, newest first: filed ones as they
 * were filed, the rest as they compute today.
 *
 * @phpstan-type Entry array{
 *     year: int,
 *     quarter: int,
 *     form: string,
 *     title: string,
 *     status: 'open'|'due'|'overdue'|'filed',
 *     amount: float,
 *     due_date: string,
 *     filed_on: string|null,
 *     paid_on: string|null,
 * }
 *
 * @phpstan-import-type YearResult from TaxCalculationService
 */
class ReturnHistory
{
    public function __construct(
        private TaxCalculationService $engine,
        private FilingCalendar $calendar,
        private VatCalculator $vat,
    ) {}

    /**
     * The forms filed for one quarter of a computed year: the quarter's
     * method decides, and a 2551Q only where percentage tax is owed (in a
     * year that left 8%, the quarter it did).
     *
     * @param  YearResult  $result
     * @return list<string>
     */
    public function formsForQuarter(TaxProfile $profile, array $result, int $quarter): array
    {
        $forms = $this->formsFor($this->engine->taxpayerFor($profile, $result, $quarter), $quarter);
        // The income tax form the engine chose: a year that left 8% files the 1701.
        $forms[0] = $result['quarters'][$quarter]['form'];
        $percentageTax = $result['quarters'][$quarter]['percentage_tax'] !== null;

        // VAT from the month after an 8% year passed ₱3,000,000.
        if (! in_array('2550Q', $forms, true) && $this->vat->appliesIn($result['year'], $quarter)) {
            $forms[] = '2550Q';
        }

        return array_values(array_filter($forms, fn (string $form) => $form !== '2551Q' || $percentageTax));
    }

    /**
     * The forms a taxpayer files for one period.
     *
     * @return list<string>
     */
    public function formsFor(TaxProfile $profile, int $quarter): array
    {
        $forms = [$this->calendar->incomeTaxForm($profile, $quarter)];

        if ($profile->regime === Regime::Graduated && ! $profile->vat_registered) {
            $forms[] = '2551Q';
        }

        if ($profile->vat_registered) {
            $forms[] = '2550Q';
        }

        return $forms;
    }

    public function filingFor(int $year, int $quarter, string $form): ?Filing
    {
        return Filing::query()->where('year', $year)->where('quarter', $quarter)->where('form', $form)->where('amendment', 0)->first();
    }

    /**
     * @return list<Entry>
     */
    public function entries(TaxProfile $profile, CarbonImmutable $today): array
    {
        $first = Transaction::query()->min('occurred_on');
        $firstYear = min($first ? CarbonImmutable::parse($first)->year : $today->year, $today->year);
        $all = Filing::query()->get();
        $filings = $all->where('amendment', 0)->keyBy(fn (Filing $f) => "{$f->year}-{$f->quarter}-{$f->form}");
        // What was paid on a return, its amendments included.
        $paid = $all->groupBy(fn (Filing $f) => "{$f->year}-{$f->quarter}-{$f->form}")
            ->map(fn ($group) => $group->whereNotNull('amount_paid')->isEmpty() ? null : (float) $group->sum(fn (Filing $f) => (float) $f->amount_paid));
        $entries = [];

        for ($year = $today->year; $year >= $firstYear; $year--) {
            $result = $this->engine->forYear($profile, $year, $today);
            $taxpayer = $profile->forYear($year);
            $vat = $this->vat->appliesFrom($year) !== null ? $this->vat->forYear($year, $today) : [];

            foreach (array_reverse($result['quarters'], true) as $quarter => $figures) {
                // Only periods that have started.
                if (CarbonImmutable::create($year, $quarter * 3 - 2)->greaterThan($today)) {
                    continue;
                }

                foreach ($this->formsForQuarter($profile, $result, $quarter) as $form) {
                    $filing = $filings->get("{$year}-{$quarter}-{$form}");
                    $percentageTax = $form === '2551Q';
                    $isVat = $form === '2550Q';
                    $amount = $filing !== null && $paid->get("{$year}-{$quarter}-{$form}") !== null
                        ? (float) $paid->get("{$year}-{$quarter}-{$form}")
                        : (float) match (true) {
                            $isVat => $vat[$quarter]['payable'],
                            $percentageTax => $figures['percentage_tax'],
                            default => $figures['due'],
                        };
                    $dueDate = match (true) {
                        $isVat => $vat[$quarter]['due_date'],
                        $percentageTax => $this->calendar->percentageTaxDue($year, $quarter)->toDateString(),
                        default => $figures['due_date'],
                    };

                    $entries[] = [
                        'year' => $year,
                        'quarter' => $quarter,
                        'form' => $form,
                        'title' => ($quarter === 4 && ! $percentageTax && ! $isVat ? 'Annual' : "Q{$quarter}").' · '
                            .match (true) {
                                $isVat => 'VAT',
                                $percentageTax => 'Percentage tax',
                                default => 'Income tax',
                            },
                        'status' => match (true) {
                            $filing !== null => 'filed',
                            ($isVat ? $vat[$quarter]['status'] : $figures['status']) === 'open' => 'open',
                            $today->toDateString() > $dueDate => 'overdue',
                            default => 'due',
                        },
                        'amount' => $amount,
                        'due_date' => $dueDate,
                        'filed_on' => $filing?->filed_on->toDateString(),
                        'paid_on' => $filing?->paid_on?->toDateString(),
                    ];
                }
            }
        }

        return $entries;
    }
}
