<?php

namespace App\Tax\Services;

use App\Profiles\Models\TaxProfile;
use App\Profiles\Models\TaxYear;
use App\Returns\Models\Filing;
use App\Returns\Services\ReturnForm;
use App\Tax\Calculation\TaxCalculator;
use App\Tax\Calculation\TaxInputs;
use App\Tax\Calculation\YearTaxpayer;
use App\Tax\Enums\Regime;
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
 *     trace: list<Step>,
 * }
 * @phpstan-type Step array{part: 'income_tax'|'percentage_tax', step: string, label: string, amount: float, rule: string|null, rate: float|null, note: string|null}
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
 * @phpstan-import-type Rules from TaxInputs
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
        private TaxCalculator $calculator,
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
     * A year's tax from the given records: gathers the inputs (each sale's
     * dates on the books' method of the year it's dated, the year's method
     * and registrations, its rules and due dates) and hands them to the
     * calculator, which reads nothing else. A year the rule pack doesn't
     * cover is refused.
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
        return $this->calculator->year($this->inputs($profile, $year, $transactions, $filings, $today, $salary));
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @param  Collection<int, Filing>  $filings
     * @param  SalaryInput|null  $salary
     */
    private function inputs(TaxProfile $profile, int $year, Collection $transactions, Collection $filings, CarbonImmutable $today, ?array $salary): TaxInputs
    {
        $taxpayer = $profile->forYear($year);
        // Each sale on the books' method of the year it's dated, so an invoice
        // counts once when the method changes between years.
        $methods = [$year => $taxpayer->books_method];
        $method = function (Transaction $t) use (&$methods, $profile) {
            return $methods[$t->occurred_on->year] ??= $profile->forYear($t->occurred_on->year)->books_method;
        };
        $dates = [];
        $quarterFilings = [];

        foreach ([1, 2, 3, 4] as $quarter) {
            $statutory = $this->calendar->incomeTaxStatutoryDate($year, $quarter);
            $due = $this->calendar->nextWorkingDay($statutory);
            $dates[$quarter] = ['statutory' => $statutory, 'due' => $due, 'moved_reason' => $this->calendar->movedReason($statutory, $due)];

            $original = $filings->first(fn (Filing $f) => $f->quarter === $quarter && $f->amendment === 0);
            $paid = $filings->filter(fn (Filing $f) => $f->quarter === $quarter && $f->amount_paid !== null);
            $quarterFilings[$quarter] = [
                'filed' => $original !== null,
                'filed_on' => $original?->filed_on->toDateString(),
                'paid_on' => $original?->paid_on?->toDateString(),
                'paid' => $paid->isEmpty() ? null : (int) $paid->sum(fn (Filing $f) => $this->cents($f->taxPaid())),
            ];
        }

        return new TaxInputs(
            year: $year,
            today: $today,
            taxpayer: YearTaxpayer::of($taxpayer),
            sales: array_values($transactions->filter(fn (Transaction $t) => $t->isIncome())->map(fn (Transaction $t) => [
                'amount' => $this->cents($t->amount),
                'withheld' => $this->cents($t->tax_withheld),
                'confirmed' => $t->certificate_received_on !== null,
                'income_date' => $t->incomeDate($method($t)),
                'sales_tax_date' => $t->salesTaxDate(),
            ])->all()),
            expenses: array_values($transactions->filter(fn (Transaction $t) => ! $t->isIncome() && $t->occurred_on->year === $year)->map(fn (Transaction $t) => [
                'date' => $t->occurred_on,
                'deductible' => $t->type === TransactionType::Expense && $t->has_valid_invoice ? $this->cents($t->amount) : 0,
            ])->all()),
            filings: $quarterFilings,
            rules: $this->ruleValues($year),
            dates: $dates,
            assignedClass: TaxYear::query()->where('year', $year)->first()?->classification,
            salary: $salary,
        );
    }

    /**
     * The year's rules as the calculator takes them, amounts in centavos.
     *
     * @return Rules
     */
    private function ruleValues(int $year): array
    {
        return [
            'brackets' => $this->rules->forYear($year)->brackets(),
            'percentage_rates' => array_combine([1, 2, 3, 4], array_map(fn (int $q) => $this->rules->forQuarter($year, $q)->rate('percentage_tax_rate'), [1, 2, 3, 4])),
            // The rules each step cites, for the trace.
            'rule_ids' => [
                'graduated' => $this->rules->forYear($year)->ruleId('graduated'),
                'percentage_tax' => array_combine([1, 2, 3, 4], array_map(fn (int $q) => $this->rules->forQuarter($year, $q)->ruleId('percentage_tax'), [1, 2, 3, 4])),
            ],
            'eight_percent_rate' => (float) config('tax.eight_percent.rate'),
            'exempt_amount' => $this->cents(config('tax.eight_percent.exempt_amount')),
            'gross_limit' => $this->cents(config('tax.eight_percent.gross_limit')),
            'osd_rate' => (float) config('tax.osd_rate'),
            'classes' => [
                'micro' => (float) config('tax.classification.micro_below'),
                'small' => (float) config('tax.classification.small_below'),
                'medium' => (float) config('tax.classification.medium_below'),
            ],
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
        return $this->calculator->incomeTax(YearTaxpayer::of($profile), $gross, $deductible, ['brackets' => $brackets] + $this->ruleValues(CarbonImmutable::now()->year));
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
     * The rules a quarter's computation applied, from its trace: the tax
     * rule first, then the deduction, then percentage tax, then the credit
     * for tax withheld.
     *
     * @param  QuarterResult  $quarter
     * @param  'income_tax'|'percentage_tax'|null  $part  only that part's, or all
     * @return list<string>
     */
    public static function rulesApplied(array $quarter, ?string $part = null): array
    {
        $steps = array_filter($quarter['trace'], fn (array $s) => $s['rule'] !== null && ($part === null || $s['part'] === $part));
        $order = ['income_tax' => 0, 'reduction' => 1, 'deduction' => 1, 'salary_tax' => 2, 'percentage_tax' => 3, 'withheld' => 4];
        usort($steps, fn (array $a, array $b) => ($order[$a['step']] ?? 2) <=> ($order[$b['step']] ?? 2));

        return array_values(array_unique(array_map(fn (array $s) => (string) $s['rule'], $steps)));
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
        return $this->calculator->graduated($taxable, $brackets);
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
        return $this->calculator->stackedGraduated($salary, $taxable, $brackets);
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
