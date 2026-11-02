<?php

namespace App\Tax\Services;

use App\Profiles\Enums\IncomeSource;
use App\Profiles\Models\TaxProfile;
use App\Returns\Models\Filing;
use App\Returns\Services\ReturnForm;
use App\Tax\Enums\BooksMethod;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Enums\Regime;
use App\Tax\Enums\TaxpayerClassification;
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
 *     regime: string|null,
 *     deduction_method: string|null,
 *     quarter_gross: float,
 *     quarter_withheld: float,
 *     gross: float,
 *     deductible_expenses: float,
 *     exempt_amount: float,
 *     deduction: float,
 *     taxable: float,
 *     income_tax: float,
 *     prior_payments: float,
 *     unpaid_earlier: float,
 *     withheld: float,
 *     withheld_unconfirmed: float,
 *     due: float,
 *     excess_credit: float,
 *     percentage_tax: float|null,
 *     percentage_tax_base: float|null,
 *     statutory_date: string,
 *     due_date: string,
 *     due_date_moved_reason: string|null,
 *     days_left: int,
 *     status: 'open'|'due'|'overdue'|'filed',
 *     filed_on: string|null,
 *     amount_paid: float|null,
 *     paid_on: string|null,
 *     compensation: Salary|null,
 * }
 * @phpstan-type Salary array{
 *     employer: string|null,
 *     employer_tin: string|null,
 *     gross: float,
 *     non_taxable: float,
 *     taxable: float,
 *     tax: float,
 *     withheld: float,
 *     source: string,
 * }
 * @phpstan-type YearResult array{
 *     year: int,
 *     regime: string|null,
 *     deduction_method: string|null,
 *     status: 'ok'|'not_applicable',
 *     gross: float,
 *     switched: Switch|null,
 *     classification: string,
 *     quarters: array<int, QuarterResult>,
 * }
 * @phpstan-type Switch array{month: int, quarter: int, vat_from: string, register_by: string}
 *
 * @phpstan-import-type SalaryInput from Compensation
 *
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
    public function __construct(
        private FilingCalendar $calendar,
        private Compensation $compensation,
        private TaxRules $rules,
    ) {}

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
            Transaction::query()->forTaxYear($year)->orderBy('occurred_on')->orderBy('id')->get(),
            Filing::query()->where('year', $year)->whereIn('form', ReturnForm::INCOME_TAX_FORMS)->get(),
            $today ?? CarbonImmutable::now(),
            $this->compensation->forYear($profile, $year),
        );
    }

    /**
     * Quarterly returns are cumulative: each one computes tax on the year so
     * far, then subtracts what earlier quarters paid and what clients withheld.
     * Quarter 4 is the annual return.
     *
     * A sale counts for income tax on the books' method (accrual: its invoice
     * date; cash: when paid), with the tax withheld from it; for percentage
     * tax on its invoice date from April 27, 2024, when paid before. Expenses
     * count on their date.
     *
     * @param  Collection<int, Transaction>  $transactions
     * @param  Collection<int, Filing>  $filings
     * @param  SalaryInput|null  $salary  a salary alongside the business, for the annual return
     * @return YearResult
     */
    public function computeYear(
        TaxProfile $profile,
        int $year,
        Collection $transactions,
        Collection $filings,
        CarbonImmutable $today,
        ?array $salary = null,
    ): array {
        $today = $today->startOfDay();
        // The year's own method and registrations, and its own rules: a year
        // the rule pack doesn't cover is refused.
        $profile = $profile->forYear($year);
        $brackets = $this->rules->forYear($year)->brackets();
        $method = $profile->books_method;
        $income = $transactions->filter(fn (Transaction $t) => $t->incomeDate($method)?->year === $year)
            ->sortBy(fn (Transaction $t) => $t->incomeDate($method)?->toDateString());
        $sales = $transactions->filter(fn (Transaction $t) => $t->salesTaxDate()?->year === $year);
        $expenses = $transactions->filter(fn (Transaction $t) => ! $t->isIncome() && $t->occurred_on->year === $year);
        $yearGross = $this->sumCents($income, 'amount');

        $status = $profile->income_source === IncomeSource::Employed ? 'not_applicable' : 'ok';
        $switched = $this->switchToGraduated($profile, $year, $income, $method);
        $classification = TaxpayerClassification::forYear($year, $this->pesos($yearGross));

        $quarters = [];
        $priorPaid = 0;
        // Earlier quarters' tax that wasn't paid and isn't credited: it's still owed.
        $unpaidEarlier = 0;

        foreach ([1, 2, 3, 4] as $quarter) {
            $toDate = $income->filter(fn (Transaction $t) => $t->incomeDate($method)?->quarter <= $quarter);
            $inQuarter = $income->filter(fn (Transaction $t) => $t->incomeDate($method)?->quarter === $quarter);

            $gross = $this->sumCents($toDate, 'amount');
            $deductible = $this->deductibleCents($expenses->filter(fn (Transaction $t) => $t->quarter() <= $quarter));
            // The tax a client withheld counts with the income it was withheld from.
            $withheld = $this->sumCents($toDate, 'tax_withheld');
            $unconfirmed = $this->sumCents(
                $toDate->filter(fn (Transaction $t) => $t->certificateStatus($today) !== CertificateStatus::Received),
                'tax_withheld',
            );
            $quarterGross = $this->sumCents($inQuarter, 'amount');
            $quarterSales = $sales->filter(fn (Transaction $t) => $t->salesTaxDate()?->quarter === $quarter);
            // Graduated rates from the quarter an 8% year passed ₱3,000,000.
            $taxpayer = $this->quarterTaxpayer($profile, $year, $quarter, $switched);

            $tax = $this->incomeTax($taxpayer, $gross, $deductible, $brackets);
            $compensation = null;
            $salaryTax = 0;
            $salaryWithheld = 0;

            // The annual return adds a salary: taxed at graduated rates, with
            // business income on graduated rates stacked on top of it, and
            // what the employer withheld credited.
            if ($quarter === 4 && $salary !== null && $taxpayer->regime !== null) {
                $salaryTaxable = max(0, $this->cents($salary['gross']) - $this->cents($salary['non_taxable']));
                $salaryTax = $this->graduated($salaryTaxable, $brackets);
                $salaryWithheld = $salary['withheld'] === null ? $salaryTax : $this->cents($salary['withheld']);

                if ($taxpayer->regime === Regime::Graduated) {
                    $tax['tax'] = $this->stackedGraduated($salaryTaxable, $tax['taxable'], $brackets);
                }

                $compensation = [
                    'employer' => $salary['employer'],
                    'employer_tin' => $salary['employer_tin'],
                    'gross' => $salary['gross'],
                    'non_taxable' => $salary['non_taxable'],
                    'taxable' => $this->pesos($salaryTaxable),
                    'tax' => $this->pesos($salaryTax),
                    'withheld' => $this->pesos($salaryWithheld),
                    'source' => $salary['source'],
                ];
            }

            $due = max(0, $tax['tax'] + $salaryTax - $withheld - $salaryWithheld - $priorPaid);

            // A year on two methods files the 1701, not the 1701-MS (1701-MS guidelines).
            $form = $switched !== null && $quarter === 4 ? '1701' : $this->calendar->incomeTaxForm($taxpayer, $quarter, $classification);
            $filing = $filings->first(fn (Filing $f) => $f->quarter === $quarter && $f->amendment === 0);
            // The tax paid for the quarter, on the original return and any amendment.
            $quarterFilings = $filings->filter(fn (Filing $f) => $f->quarter === $quarter && $f->amount_paid !== null);
            $quarterPaid = $quarterFilings->isEmpty() ? null : $quarterFilings->sum(fn (Filing $f) => $this->cents($f->taxPaid()));
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
                'regime' => $taxpayer->regime?->value,
                // Only graduated rates deduct anything.
                'deduction_method' => $taxpayer->regime === Regime::Graduated ? $taxpayer->deduction_method?->value : null,
                'quarter_gross' => $this->pesos($quarterGross),
                'quarter_withheld' => $this->pesos($this->sumCents($inQuarter, 'tax_withheld')),
                'gross' => $this->pesos($gross),
                'deductible_expenses' => $this->pesos($deductible),
                'exempt_amount' => $this->pesos($tax['exempt']),
                'deduction' => $this->pesos($tax['deduction']),
                'taxable' => $this->pesos($tax['taxable']),
                'income_tax' => $this->pesos($tax['tax']),
                'prior_payments' => $this->pesos($priorPaid),
                'unpaid_earlier' => $this->pesos($unpaidEarlier),
                'withheld' => $this->pesos($withheld),
                'withheld_unconfirmed' => $this->pesos($unconfirmed),
                'due' => $this->pesos($due),
                'excess_credit' => $this->pesos(max(0, $withheld + $salaryWithheld + $priorPaid - $tax['tax'] - $salaryTax)),
                ...$this->quarterPercentageTax($taxpayer, $year, $quarter, $quarterSales, $sales, $switched),
                'statutory_date' => $statutory->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'due_date_moved_reason' => $this->calendar->movedReason($statutory, $dueDate),
                'days_left' => (int) round($today->diffInDays($dueDate, false)),
                'status' => $quarterStatus,
                'filed_on' => $filing?->filed_on->toDateString(),
                'amount_paid' => $quarterPaid !== null ? $this->pesos($quarterPaid) : null,
                'paid_on' => $filing?->paid_on?->toDateString(),
                'compensation' => $compensation,
            ];

            // What later quarters subtract: the payment recorded, or, while the
            // quarter can still be paid on time, the tax it owes. One overdue,
            // or filed with nothing paid, isn't credited: its tax stays in the
            // later returns, as the 1701Q's "tax paid in previous quarters" has it.
            $credited = match (true) {
                $quarterPaid !== null => $quarterPaid,
                $filing === null && $quarterStatus !== 'overdue' => $due,
                default => 0,
            };
            $priorPaid += $credited;
            $unpaidEarlier += $quarterPaid === null ? $due - $credited : max(0, $due - $quarterPaid);
        }

        return [
            'year' => $year,
            'regime' => $profile->regime?->value,
            'deduction_method' => $profile->deduction_method?->value,
            'status' => $status,
            'gross' => $this->pesos($yearGross),
            'switched' => $switched,
            'classification' => $classification->value,
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
        $effects = [];

        // Each year on its own rules, from nothing.
        foreach ($transactions->groupBy(fn (Transaction $t) => $t->occurred_on->year) as $year => $inYear) {
            $brackets = $this->rules->forYear((int) $year)->brackets();
            $taxpayer = $profile->forYear((int) $year);
            $gross = 0;
            $deductible = 0;
            $percentageTax = 0;
            $before = $this->totalTax($taxpayer, (int) $year, 0, 0, 0, $brackets);

            foreach ($inYear->sortBy([['occurred_on', 'asc'], ['id', 'asc']]) as $transaction) {
                // On cash books an unpaid invoice doesn't count yet.
                if ($transaction->isIncome() && $transaction->incomeDate($taxpayer->books_method) === null) {
                    $effects[$transaction->id] = 0.0;

                    continue;
                }

                if ($transaction->isIncome()) {
                    $gross += $this->cents($transaction->amount);
                    $percentageTax += $this->percentageTaxOn($transaction);
                } elseif ($transaction->has_valid_invoice) {
                    $deductible += $this->cents($transaction->amount);
                }

                $after = $this->totalTax($taxpayer, (int) $year, $gross, $deductible, $percentageTax, $brackets);
                $effects[$transaction->id] = $this->pesos($after - $before);
                $before = $after;
            }
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
        $profile = $profile->forYear($candidate->occurred_on->year);
        $others = $yearTransactions->reject(fn (Transaction $t) => $candidate->exists && $t->is($candidate));
        $income = $others->filter(fn (Transaction $t) => $t->incomeDate($profile->books_method) !== null);
        $gross = $this->sumCents($income, 'amount');
        $deductible = $this->deductibleCents($others);
        $percentageTax = (int) $income->sum(fn (Transaction $t) => $this->percentageTaxOn($t));
        $brackets = $this->rules->forYear($candidate->occurred_on->year)->brackets();

        $amount = $this->cents($candidate->amount);
        $year = $candidate->occurred_on->year;
        $before = $this->totalTax($profile, $year, $gross, $deductible, $percentageTax, $brackets);
        $after = match (true) {
            $candidate->incomeDate($profile->books_method) !== null => $this->totalTax($profile, $year, $gross + $amount, $deductible, $percentageTax + $this->percentageTaxOn($candidate), $brackets),
            $candidate->has_valid_invoice => $this->totalTax($profile, $year, $gross, $deductible + $amount, $percentageTax, $brackets),
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
    public function compare(float $income, int $expensePercent, ?float $salary = null, ?int $year = null): array
    {
        // Planning is for this year unless said otherwise.
        $rules = $this->rules->forYear($year ?? CarbonImmutable::now()->year);
        $brackets = $rules->brackets();
        $gross = $this->cents($income);
        $expenses = $this->rate($gross, $expensePercent / 100);
        $salaryCents = $salary !== null ? $this->cents($salary) : 0;
        $overLimit = $gross > $this->cents(config('tax.eight_percent.gross_limit'));
        // Above the limit the taxpayer must register for VAT instead.
        $percentageTax = $overLimit ? 0 : $this->rate($gross, $rules->rate('percentage_tax_rate'));

        $exempt = $salaryCents > 0 ? 0 : min($gross, $this->cents(config('tax.eight_percent.exempt_amount')));
        $osdTaxable = $gross - $this->rate($gross, config('tax.osd_rate'));
        $itemizedTaxable = max(0, $gross - $expenses);

        $options = [
            $this->option('eight_percent', ! $overLimit, $exempt, $gross - $exempt, $this->rate($gross - $exempt, config('tax.eight_percent.rate')), 0),
            $this->option('graduated_osd', true, 0, $osdTaxable, $this->stackedGraduated($salaryCents, $osdTaxable, $brackets), $percentageTax),
            $this->option('graduated_itemized', true, 0, $itemizedTaxable, $this->stackedGraduated($salaryCents, $itemizedTaxable, $brackets), $percentageTax),
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
     * Graduated tax on an annual taxable amount, in pesos, on a year's table
     * (this year's unless said otherwise).
     */
    public function graduatedTax(float $taxable, ?int $year = null): float
    {
        return $this->pesos($this->graduated($this->cents($taxable), $this->rules->forYear($year ?? CarbonImmutable::now()->year)->brackets()));
    }

    /**
     * @param  list<array{int|float, int|float, float}>  $brackets
     * @return array{exempt: int, deduction: int, taxable: int, tax: int}
     */
    private function incomeTax(TaxProfile $profile, int $gross, int $deductible, array $brackets): array
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
            'tax' => $this->graduated($taxable, $brackets),
        ];
    }

    /**
     * Income tax plus any percentage tax for a year, in centavos. Percentage
     * tax is added up by the caller, each sale at its own quarter's rate.
     * Past ₱3,000,000 an 8% year is on graduated rates; the percentage tax
     * then stands in for the VAT on later sales, which isn't computed.
     *
     * @param  list<array{int|float, int|float, float}>  $brackets
     */
    private function totalTax(TaxProfile $profile, int $year, int $gross, int $deductible, int $percentageTax, array $brackets): int
    {
        if ($profile->regime === Regime::EightPercent && $gross > $this->cents(config('tax.eight_percent.gross_limit'))) {
            $profile = $profile->onGraduatedRates($year);
        }

        return $this->incomeTax($profile, $gross, $deductible, $brackets)['tax'] + ($this->owesPercentageTax($profile) ? $percentageTax : 0);
    }

    /**
     * The profile a quarter of a computed year is taxed on: the year's, on
     * graduated rates from the quarter an 8% year passed ₱3,000,000.
     *
     * @param  YearResult  $result
     */
    public function taxpayerFor(TaxProfile $profile, array $result, int $quarter): TaxProfile
    {
        return $this->quarterTaxpayer($profile->forYear($result['year']), $result['year'], $quarter, $result['switched']);
    }

    /**
     * @param  Switch|null  $switched
     */
    private function quarterTaxpayer(TaxProfile $profile, int $year, int $quarter, ?array $switched): TaxProfile
    {
        return $switched !== null && $quarter >= $switched['quarter'] ? $profile->onGraduatedRates($year) : $profile;
    }

    /**
     * An 8% year whose sales pass ₱3,000,000 is on graduated rates from
     * then on, with the 8% paid before credited (RR 8-2018). The
     * registration is updated within the next month, VAT applies from the
     * first day of that month, and percentage tax is owed on the sales up
     * to it. The limit is "exceeded": exactly ₱3,000,000 stays on 8%.
     *
     * @param  Collection<int, Transaction>  $income
     * @return Switch|null
     */
    private function switchToGraduated(TaxProfile $profile, int $year, Collection $income, BooksMethod $method): ?array
    {
        if ($profile->regime !== Regime::EightPercent) {
            return null;
        }

        $limit = $this->cents(config('tax.eight_percent.gross_limit'));
        $total = 0;

        foreach ($income as $sale) {
            $total += $this->cents($sale->amount);
            $on = $sale->incomeDate($method) ?? $sale->occurred_on;

            if ($total > $limit) {
                $next = CarbonImmutable::create($year, $on->month)->addMonth();

                return [
                    'month' => $on->month,
                    'quarter' => $on->quarter,
                    'vat_from' => $next->toDateString(),
                    'register_by' => $next->endOfMonth()->toDateString(),
                ];
            }
        }

        return null;
    }

    /**
     * A quarter's percentage tax and the sales it's on. In a year that left
     * 8%, it's all on the 2551Q of the quarter the limit was passed: the
     * sales from January to that month, payable then without penalty; VAT
     * replaces it after.
     *
     * @param  Collection<int, Transaction>  $quarterSales
     * @param  Collection<int, Transaction>  $sales  the year's
     * @param  Switch|null  $switched
     * @return array{percentage_tax: float|null, percentage_tax_base: float|null}
     */
    private function quarterPercentageTax(TaxProfile $taxpayer, int $year, int $quarter, Collection $quarterSales, Collection $sales, ?array $switched): array
    {
        $none = ['percentage_tax' => null, 'percentage_tax_base' => null];

        if ($switched === null) {
            $base = $this->sumCents($quarterSales, 'amount');

            return $this->owesPercentageTax($taxpayer)
                ? ['percentage_tax' => $this->pesos($this->rate($base, $this->rules->forQuarter($year, $quarter)->rate('percentage_tax_rate'))), 'percentage_tax_base' => $this->pesos($base)]
                : $none;
        }

        if ($quarter !== $switched['quarter']) {
            return $none;
        }

        $toSwitch = $sales->filter(fn (Transaction $t) => $t->salesTaxDate()?->month <= $switched['month']);

        return [
            'percentage_tax' => $this->pesos((int) $toSwitch->sum(fn (Transaction $t) => $this->percentageTaxOn($t))),
            'percentage_tax_base' => $this->pesos($this->sumCents($toSwitch, 'amount')),
        ];
    }

    /**
     * The percentage tax on one sale, at its quarter's rate, in centavos.
     */
    private function percentageTaxOn(Transaction $sale): int
    {
        $on = $sale->salesTaxDate() ?? $sale->occurred_on;

        return $this->rate($this->cents($sale->amount), $this->rules->forQuarter($on->year, $on->quarter)->rate('percentage_tax_rate'));
    }

    private function owesPercentageTax(TaxProfile $profile): bool
    {
        return $profile->regime === Regime::Graduated && ! $profile->vat_registered;
    }

    /**
     * @param  list<array{int|float, int|float, float}>  $brackets
     */
    private function graduated(int $taxable, array $brackets): int
    {
        $tax = 0;

        foreach ($brackets as [$over, $base, $rate]) {
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
    /**
     * @param  list<array{int|float, int|float, float}>  $brackets
     */
    private function stackedGraduated(int $salary, int $taxable, array $brackets): int
    {
        return $this->graduated($salary + $taxable, $brackets) - $this->graduated($salary, $brackets);
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
