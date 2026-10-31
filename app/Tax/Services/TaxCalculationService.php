<?php

namespace App\Tax\Services;

use App\Profiles\Enums\IncomeSource;
use App\Profiles\Models\TaxProfile;
use App\Returns\Models\Filing;
use App\Returns\Services\ReturnForm;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Enums\Regime;
use App\Transactions\Enums\CertificateStatus;
use App\Transactions\Enums\TransactionType;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * The tax engine. Every figure the app shows comes from here, computed with
 * the rates in config/tax.php.
 *
 * Internally everything is in integer centavos so sums never drift; results
 * are returned in pesos.
 *
 * @phpstan-type QuarterResult array{
 *     quarter: int,
 *     form: string,
 *     quarter_gross: float,
 *     quarter_withheld: float,
 *     gross: float,
 *     deductible_expenses: float,
 *     exempt_amount: float,
 *     deduction: float,
 *     taxable: float,
 *     income_tax: float,
 *     prior_payments: float,
 *     withheld: float,
 *     withheld_unconfirmed: float,
 *     due: float,
 *     excess_credit: float,
 *     percentage_tax: float|null,
 *     statutory_date: string,
 *     due_date: string,
 *     due_date_moved_reason: string|null,
 *     days_left: int,
 *     status: 'open'|'due'|'overdue'|'filed',
 *     filed_on: string|null,
 *     amount_paid: float|null,
 *     paid_on: string|null,
 * }
 * @phpstan-type YearResult array{
 *     year: int,
 *     regime: string|null,
 *     deduction_method: string|null,
 *     status: 'ok'|'cannot_compute'|'not_applicable',
 *     gross: float,
 *     quarters: array<int, QuarterResult>,
 * }
 * @phpstan-type CompareOption array{
 *     key: 'eight_percent'|'graduated_osd'|'graduated_itemized',
 *     available: bool,
 *     exempt_amount: float,
 *     taxable: float,
 *     income_tax: float,
 *     percentage_tax: float,
 *     total: float,
 * }
 */
class TaxCalculationService
{
    public function __construct(private FilingCalendar $calendar) {}

    /**
     * Compute a year from what is recorded.
     *
     * @return YearResult
     */
    public function forYear(TaxProfile $profile, int $year, ?CarbonImmutable $today = null): array
    {
        return $this->computeYear(
            $profile,
            $year,
            Transaction::query()->forYear($year)->orderBy('occurred_on')->orderBy('id')->get(),
            Filing::query()->where('year', $year)->whereIn('form', ReturnForm::INCOME_TAX_FORMS)->get(),
            $today ?? CarbonImmutable::now(),
        );
    }

    /**
     * Quarterly returns are cumulative: each one computes tax on the year so
     * far, then subtracts what earlier quarters paid and what clients withheld.
     * Quarter 4 is the annual return.
     *
     * @param  Collection<int, Transaction>  $transactions
     * @param  Collection<int, Filing>  $filings
     * @return YearResult
     */
    public function computeYear(
        TaxProfile $profile,
        int $year,
        Collection $transactions,
        Collection $filings,
        CarbonImmutable $today,
    ): array {
        $today = $today->startOfDay();
        $income = $transactions->filter(fn (Transaction $t) => $t->isIncome());
        $yearGross = $this->sumCents($income, 'amount');

        $status = match (true) {
            $profile->income_source === IncomeSource::Employed => 'not_applicable',
            $profile->regime === Regime::EightPercent && $yearGross > $this->cents(config('tax.eight_percent.gross_limit')) => 'cannot_compute',
            default => 'ok',
        };

        $quarters = [];
        $priorPaid = 0;

        foreach ([1, 2, 3, 4] as $quarter) {
            $toDate = $transactions->filter(fn (Transaction $t) => $t->quarter() <= $quarter);
            $inQuarter = $transactions->filter(fn (Transaction $t) => $t->quarter() === $quarter);

            $gross = $this->sumCents($toDate->filter(fn (Transaction $t) => $t->isIncome()), 'amount');
            $deductible = $this->deductibleCents($toDate);
            $withheld = $this->sumCents($toDate, 'tax_withheld');
            $unconfirmed = $this->sumCents(
                $toDate->filter(fn (Transaction $t) => $t->certificateStatus($today) !== CertificateStatus::Received),
                'tax_withheld',
            );
            $quarterGross = $this->sumCents($inQuarter->filter(fn (Transaction $t) => $t->isIncome()), 'amount');

            $tax = $this->incomeTax($profile, $gross, $deductible);
            $due = max(0, $tax['tax'] - $withheld - $priorPaid);

            $form = $this->calendar->incomeTaxForm($profile, $quarter);
            $filing = $filings->first(fn (Filing $f) => $f->quarter === $quarter);
            $statutory = $this->calendar->incomeTaxStatutoryDate($year, $quarter);
            $dueDate = $this->calendar->nextWorkingDay($statutory);
            $periodEnd = CarbonImmutable::create($year, $quarter * 3)->endOfMonth()->startOfDay();

            $quarterStatus = match (true) {
                $filing !== null => 'filed',
                $today->lessThanOrEqualTo($periodEnd) => 'open',
                $today->greaterThan($dueDate) => 'overdue',
                default => 'due',
            };

            $quarters[$quarter] = [
                'quarter' => $quarter,
                'form' => $form,
                'quarter_gross' => $this->pesos($quarterGross),
                'quarter_withheld' => $this->pesos($this->sumCents($inQuarter, 'tax_withheld')),
                'gross' => $this->pesos($gross),
                'deductible_expenses' => $this->pesos($deductible),
                'exempt_amount' => $this->pesos($tax['exempt']),
                'deduction' => $this->pesos($tax['deduction']),
                'taxable' => $this->pesos($tax['taxable']),
                'income_tax' => $this->pesos($tax['tax']),
                'prior_payments' => $this->pesos($priorPaid),
                'withheld' => $this->pesos($withheld),
                'withheld_unconfirmed' => $this->pesos($unconfirmed),
                'due' => $this->pesos($due),
                'excess_credit' => $this->pesos(max(0, $withheld + $priorPaid - $tax['tax'])),
                'percentage_tax' => $this->owesPercentageTax($profile)
                    ? $this->pesos($this->rate($quarterGross, config('tax.percentage_tax_rate')))
                    : null,
                'statutory_date' => $statutory->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'due_date_moved_reason' => $this->calendar->movedReason($statutory, $dueDate),
                'days_left' => (int) round($today->diffInDays($dueDate, false)),
                'status' => $quarterStatus,
                'filed_on' => $filing?->filed_on->toDateString(),
                'amount_paid' => $filing?->amount_paid !== null ? (float) $filing->amount_paid : null,
                'paid_on' => $filing?->paid_on?->toDateString(),
            ];

            // What later quarters subtract: the payment made, or the amount owed.
            $priorPaid += $filing?->amount_paid !== null ? $this->cents($filing->amount_paid) : $due;
        }

        return [
            'year' => $year,
            'regime' => $profile->regime?->value,
            'deduction_method' => $profile->deduction_method?->value,
            'status' => $status,
            'gross' => $this->pesos($yearGross),
            'quarters' => $quarters,
        ];
    }

    /**
     * How much each transaction moved the year's total tax, in date order.
     *
     * @param  Collection<int, Transaction>  $transactions
     * @return array<int, float> pesos, keyed by transaction id
     */
    public function transactionEffects(TaxProfile $profile, Collection $transactions): array
    {
        $gross = 0;
        $deductible = 0;
        $before = $this->totalTax($profile, 0, 0);
        $effects = [];

        foreach ($transactions->sortBy([['occurred_on', 'asc'], ['id', 'asc']]) as $transaction) {
            if ($transaction->isIncome()) {
                $gross += $this->cents($transaction->amount);
            } elseif ($transaction->has_valid_invoice) {
                $deductible += $this->cents($transaction->amount);
            }

            $after = $this->totalTax($profile, $gross, $deductible);
            $effects[$transaction->id] = $this->pesos($after - $before);
            $before = $after;
        }

        return $effects;
    }

    /**
     * What saving one more transaction would do to the year's tax.
     *
     * @param  Collection<int, Transaction>  $yearTransactions
     * @return array{quarter: int, tax_effect: float, credit: float}
     */
    public function previewEffect(TaxProfile $profile, Collection $yearTransactions, Transaction $candidate): array
    {
        $others = $yearTransactions->reject(fn (Transaction $t) => $candidate->exists && $t->is($candidate));
        $gross = $this->sumCents($others->filter(fn (Transaction $t) => $t->isIncome()), 'amount');
        $deductible = $this->deductibleCents($others);

        $amount = $this->cents($candidate->amount);
        $before = $this->totalTax($profile, $gross, $deductible);
        $after = match (true) {
            $candidate->isIncome() => $this->totalTax($profile, $gross + $amount, $deductible),
            $candidate->has_valid_invoice => $this->totalTax($profile, $gross, $deductible + $amount),
            default => $before,
        };

        return [
            'quarter' => $candidate->quarter(),
            'tax_effect' => $this->pesos($after - $before),
            'credit' => $this->pesos($this->cents($candidate->tax_withheld)),
        ];
    }

    /**
     * Total tax for a year under each method, for planning.
     *
     * @return array{
     *     income: float,
     *     expense_percent: int,
     *     expenses: float,
     *     salary: float|null,
     *     over_limit: bool,
     *     options: list<CompareOption>,
     *     best: string|null,
     *     savings: float,
     * }
     */
    public function compare(float $income, int $expensePercent, ?float $salary = null): array
    {
        $gross = $this->cents($income);
        $expenses = $this->rate($gross, $expensePercent / 100);
        $salaryCents = $salary !== null ? $this->cents($salary) : 0;
        $overLimit = $gross > $this->cents(config('tax.eight_percent.gross_limit'));
        // Above the limit the taxpayer must register for VAT instead.
        $percentageTax = $overLimit ? 0 : $this->rate($gross, config('tax.percentage_tax_rate'));

        $exempt = $salaryCents > 0 ? 0 : min($gross, $this->cents(config('tax.eight_percent.exempt_amount')));
        $osdTaxable = $gross - $this->rate($gross, config('tax.osd_rate'));
        $itemizedTaxable = max(0, $gross - $expenses);

        $options = [
            $this->option('eight_percent', ! $overLimit, $exempt, $gross - $exempt, $this->rate($gross - $exempt, config('tax.eight_percent.rate')), 0),
            $this->option('graduated_osd', true, 0, $osdTaxable, $this->stackedGraduated($salaryCents, $osdTaxable), $percentageTax),
            $this->option('graduated_itemized', true, 0, $itemizedTaxable, $this->stackedGraduated($salaryCents, $itemizedTaxable), $percentageTax),
        ];

        $ranked = collect($options)->where('available', true)->sortBy('total')->values();

        return [
            'income' => $this->pesos($gross),
            'expense_percent' => $expensePercent,
            'expenses' => $this->pesos($expenses),
            'salary' => $salary,
            'over_limit' => $overLimit,
            'options' => $options,
            'best' => $ranked->first()['key'] ?? null,
            'savings' => $ranked->count() > 1 ? round($ranked[1]['total'] - $ranked[0]['total'], 2) : 0.0,
        ];
    }

    /**
     * Graduated tax on an annual taxable amount, in pesos.
     */
    public function graduatedTax(float $taxable): float
    {
        return $this->pesos($this->graduated($this->cents($taxable)));
    }

    /**
     * @return array{exempt: int, deduction: int, taxable: int, tax: int}
     */
    private function incomeTax(TaxProfile $profile, int $gross, int $deductible): array
    {
        if ($profile->income_source === IncomeSource::Employed || $profile->regime === null) {
            return ['exempt' => 0, 'deduction' => 0, 'taxable' => 0, 'tax' => 0];
        }

        if ($profile->regime === Regime::EightPercent) {
            $exempt = $profile->isPurelySelfEmployed()
                ? min($gross, $this->cents(config('tax.eight_percent.exempt_amount')))
                : 0;
            $taxable = $gross - $exempt;

            return [
                'exempt' => $exempt,
                'deduction' => 0,
                'taxable' => $taxable,
                'tax' => $this->rate($taxable, config('tax.eight_percent.rate')),
            ];
        }

        $deduction = $profile->deduction_method === DeductionMethod::Itemized
            ? min($gross, $deductible)
            : $this->rate($gross, config('tax.osd_rate'));
        $taxable = $gross - $deduction;

        return [
            'exempt' => 0,
            'deduction' => $deduction,
            'taxable' => $taxable,
            'tax' => $this->graduated($taxable),
        ];
    }

    /**
     * Income tax plus any percentage tax for a year, in centavos.
     */
    private function totalTax(TaxProfile $profile, int $gross, int $deductible): int
    {
        $percentageTax = $this->owesPercentageTax($profile)
            ? $this->rate($gross, config('tax.percentage_tax_rate'))
            : 0;

        return $this->incomeTax($profile, $gross, $deductible)['tax'] + $percentageTax;
    }

    private function owesPercentageTax(TaxProfile $profile): bool
    {
        return $profile->regime === Regime::Graduated && ! $profile->vat_registered;
    }

    private function graduated(int $taxable): int
    {
        $tax = 0;

        foreach (config('tax.graduated_brackets') as [$over, $base, $rate]) {
            $overCents = $this->cents($over);

            if ($taxable > $overCents) {
                $tax = $this->cents($base) + $this->rate($taxable - $overCents, $rate);
            }
        }

        return $tax;
    }

    /**
     * Graduated tax on business income stacked on top of a salary, so the
     * salary's own tax (the same under every option) is left out.
     */
    private function stackedGraduated(int $salary, int $taxable): int
    {
        return $this->graduated($salary + $taxable) - $this->graduated($salary);
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     */
    private function deductibleCents(Collection $transactions): int
    {
        return $this->sumCents(
            $transactions->filter(fn (Transaction $t) => $t->type === TransactionType::Expense && $t->has_valid_invoice),
            'amount',
        );
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     */
    private function sumCents(Collection $transactions, string $attribute): int
    {
        return (int) $transactions->sum(fn (Transaction $t) => $this->cents($t->getAttribute($attribute)));
    }

    /**
     * @param  'eight_percent'|'graduated_osd'|'graduated_itemized'  $key
     * @return CompareOption
     */
    private function option(string $key, bool $available, int $exempt, int $taxable, int $incomeTax, int $percentageTax): array
    {
        return [
            'key' => $key,
            'available' => $available,
            'exempt_amount' => $this->pesos($exempt),
            'taxable' => $this->pesos($taxable),
            'income_tax' => $this->pesos($incomeTax),
            'percentage_tax' => $this->pesos($percentageTax),
            'total' => $this->pesos($incomeTax + $percentageTax),
        ];
    }

    private function rate(int $cents, float $rate): int
    {
        return (int) round($cents * $rate);
    }

    private function cents(string|int|float|null $pesos): int
    {
        return (int) round(((float) $pesos) * 100);
    }

    private function pesos(int $cents): float
    {
        return $cents / 100;
    }
}
