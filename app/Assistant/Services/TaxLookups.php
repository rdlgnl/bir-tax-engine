<?php

namespace App\Assistant\Services;

use App\Compliance\Services\TaxObligations;
use App\Letters\Models\Letter;
use App\Letters\Services\LetterCheck;
use App\Profiles\Enums\IncomeSource;
use App\Profiles\Models\TaxProfile;
use App\Tax\Services\RuleReview;
use App\Tax\Services\TaxCalculationService;
use App\Tax\Services\TaxRules;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * What the assistant can look up: the tax engine's figures and the rule
 * pack, read-only. Each lookup returns JSON for the model and the figures
 * it used, for the page to show beside the answer. The suggestions it can
 * put to the taxpayer come through here too (see Suggestions).
 *
 * Only figures and tax choices are shared: never the taxpayer's name, TIN
 * or address, nor the names of their clients or employer.
 *
 * @phpstan-type Figure array{string, string}
 *
 * @phpstan-import-type Suggestion from Suggestions
 *
 * @phpstan-type Result array{content: string, figures: list<Figure>, suggestion?: Suggestion}
 * @phpstan-type Definition array{name: string, description: string, schema: array<string, mixed>}
 */
class TaxLookups
{
    public function __construct(
        private TaxCalculationService $engine,
        private TaxObligations $obligations,
        private TaxRules $rules,
        private LetterCheck $letterCheck,
        private Suggestions $suggestions,
    ) {}

    /**
     * @return list<Definition>
     */
    public function definitions(): array
    {
        $year = ['type' => 'integer', 'description' => 'The tax year, e.g. 2026.'];

        return [
            [
                'name' => 'tax_year',
                'description' => "A tax year as the app computes it: the tax method and registrations that year, the sales so far, and each quarter's income tax return (quarter 4 is the annual return): sales, deduction, taxable income, tax, credits, the amount payable, the percentage tax, the due date, and whether it was filed and paid.",
                'schema' => ['type' => 'object', 'properties' => ['year' => $year], 'required' => ['year']],
            ],
            [
                'name' => 'quarter_steps',
                'description' => "How the app computed one quarter's income tax and percentage tax, step by step, each with the rule-pack rule it applied. Use it to explain why a tax is what it is.",
                'schema' => [
                    'type' => 'object',
                    'properties' => ['year' => $year, 'quarter' => ['type' => 'integer', 'description' => '1 to 3 for the quarterly returns, 4 for the annual return.']],
                    'required' => ['year', 'quarter'],
                ],
            ],
            [
                'name' => 'deadlines',
                'description' => "The returns and payments coming due, and any overdue, with their amounts, from the app's filing calendar.",
                'schema' => ['type' => 'object', 'properties' => new \stdClass],
            ],
            [
                'name' => 'compare_methods',
                'description' => "The app's comparison of the 8% option with graduated rates (optional standard deduction or itemized) for a year's expected sales. Inputs left out come from the taxpayer's profile.",
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'expected_sales' => ['type' => 'number', 'description' => "The year's expected gross sales in pesos."],
                        'expense_percent' => ['type' => 'integer', 'description' => 'Expenses as a percentage of sales, 0 to 100.'],
                        'annual_salary' => ['type' => 'number', 'description' => 'A salary for the year in pesos, if they also have a job.'],
                    ],
                ],
            ],
            [
                'name' => 'letters',
                'description' => "The letters from the BIR the taxpayer has added to the app, newest first: what kind each is, the return and period it names, the date to reply by, whether it's resolved, and what the app found when it checked the letter against their records.",
                'schema' => ['type' => 'object', 'properties' => new \stdClass],
            ],
            [
                'name' => 'rules',
                'description' => 'The rules in the rule pack: each id, what it is, its legal basis, when it took effect, and whether a CPA has reviewed it.',
                'schema' => ['type' => 'object', 'properties' => new \stdClass],
            ],
            ...$this->suggestions->definitions(),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return Result
     *
     * @throws InvalidArgumentException for a lookup or input that doesn't exist; the model is told so
     */
    public function run(string $name, array $input): array
    {
        if ($this->suggestions->handles($name)) {
            ['content' => $content, 'suggestion' => $suggestion] = $this->suggestions->run($name, $input);

            return ['content' => $content, 'figures' => [], 'suggestion' => $suggestion];
        }

        $profile = TaxProfile::current() ?? throw new InvalidArgumentException('There is no tax profile yet.');

        return match ($name) {
            'tax_year' => $this->taxYear($profile, $this->year($input)),
            'quarter_steps' => $this->quarterSteps($profile, $this->year($input), $this->quarter($input)),
            'deadlines' => $this->deadlines($profile),
            'compare_methods' => $this->compare($profile, $input),
            'letters' => $this->letters($profile),
            'rules' => $this->ruleList(),
            default => throw new InvalidArgumentException("There's no lookup called {$name}."),
        };
    }

    /**
     * @return Result
     */
    private function taxYear(TaxProfile $profile, int $year): array
    {
        $result = $this->engine->forYear($profile, $year);
        $then = $profile->forYear($year);
        $quarters = [];
        $figures = [["{$year} sales so far", $this->peso($result['gross'])]];

        foreach ($result['quarters'] as $q) {
            $label = $q['quarter'] === 4 ? "{$year} annual return ({$q['form']})" : "Q{$q['quarter']} {$q['form']}";
            $quarters[] = [
                'quarter' => $q['quarter'],
                'return' => $q['form'],
                'method' => $this->method($q['regime'], $q['deduction_method']),
                'sales_this_quarter' => $q['quarter_gross'],
                'sales_to_date' => $q['gross'],
                'deduction' => $q['deduction'],
                'taxable_income_to_date' => $q['taxable'],
                'income_tax_to_date' => $q['income_tax'],
                'paid_in_earlier_returns' => $q['prior_payments'],
                'withheld_by_clients' => $q['withheld'],
                'withheld_without_2307_yet' => $q['withheld_unconfirmed'],
                'tax_payable' => $q['due'],
                'percentage_tax' => $q['percentage_tax'],
                'due_date' => $q['due_date'],
                'status' => $q['status'],
                'filed_on' => $q['filed_on'],
                'amount_paid' => $q['amount_paid'],
                'salary' => $q['compensation'] === null ? null : [
                    'taxable' => $q['compensation']['taxable'],
                    'tax' => $q['compensation']['tax'],
                    'withheld_by_employer' => $q['compensation']['withheld'],
                ],
            ];

            if ($q['status'] !== 'open' || $q['quarter_gross'] > 0) {
                $figures[] = ["{$label} payable", $this->peso($q['due'])];
            }

            if ($q['percentage_tax'] !== null && $q['percentage_tax'] > 0) {
                $figures[] = ["Q{$q['quarter']} percentage tax", $this->peso($q['percentage_tax'])];
            }
        }

        return $this->result([
            'year' => $year,
            'as_of' => CarbonImmutable::now()->toDateString(),
            'method' => $this->method($result['regime'], $result['deduction_method']),
            'income' => $then->income_source === IncomeSource::Both ? 'a job and a business' : 'business or profession only',
            'registered_for' => [
                'income_tax' => (bool) $then->registered_income_tax,
                'percentage_tax' => (bool) $then->registered_percentage_tax,
                'vat' => (bool) $then->vat_registered,
                'withholding_on_payments_to_others' => (bool) $then->withholding_agent,
            ],
            'classification' => $result['classification'],
            'books_method' => $then->books_method->value,
            'sales_so_far' => $result['gross'],
            'passed_3m_on_8_percent' => $result['switched'],
            'quarters' => $quarters,
        ], $figures);
    }

    /**
     * @return Result
     */
    private function quarterSteps(TaxProfile $profile, int $year, int $quarter): array
    {
        $q = $this->engine->forYear($profile, $year)['quarters'][$quarter];
        $rules = (array) config('tax.rules');
        $steps = array_map(fn (array $step) => [
            'step' => $step['label'],
            'amount' => $step['amount'],
            'rate' => $step['rate'],
            'rule' => $step['rule'],
            'rule_title' => $step['rule'] !== null ? ($rules[$step['rule']]['title'] ?? null) : null,
            'note' => $step['note'],
        ], $q['trace']);
        $label = $quarter === 4 ? "{$year} annual return" : "Q{$quarter} {$year}";

        return $this->result(
            ['year' => $year, 'quarter' => $quarter, 'return' => $q['form'], 'steps' => $steps, 'tax_payable' => $q['due']],
            [["{$label} tax payable", $this->peso($q['due'])]],
        );
    }

    /**
     * @return Result
     */
    private function deadlines(TaxProfile $profile): array
    {
        $today = CarbonImmutable::now();
        $coming = [];

        foreach ([$today->year - 1, $today->year] as $year) {
            if ($year < $this->rules->firstDate()->year) {
                continue;
            }

            foreach ($this->obligations->deadlines($profile, $this->engine->forYear($profile, $year, $today)) as $deadline) {
                if ($deadline['status'] === 'overdue' || $deadline['date'] >= $today->toDateString()) {
                    $coming[$deadline['date'].$deadline['title']] = [
                        'date' => $deadline['date'],
                        'what' => $deadline['title'],
                        'amount' => $deadline['amount'],
                        'status' => $deadline['status'],
                        'note' => $deadline['note'],
                    ];
                }
            }
        }

        ksort($coming);
        $coming = array_slice(array_values($coming), 0, 10);
        $next = $coming[0] ?? null;

        return $this->result(
            ['today' => $today->toDateString(), 'deadlines' => $coming],
            $next === null ? [] : [['Next: '.$next['what'], $next['amount'] !== null ? $this->peso($next['amount']).' · '.CarbonImmutable::parse($next['date'])->format('M j') : CarbonImmutable::parse($next['date'])->format('M j')]],
        );
    }

    /**
     * @param  array<string, mixed>  $input
     * @return Result
     */
    private function compare(TaxProfile $profile, array $input): array
    {
        $sales = $this->number($input, 'expected_sales', 0, 999_999_999) ?? (float) $profile->expected_income;
        $expenses = $this->number($input, 'expense_percent', 0, 100) ?? (float) ($profile->expected_expense_percent ?? 0);
        $salary = $this->number($input, 'annual_salary', 0, 999_999_999)
            ?? ($profile->income_source === IncomeSource::Both && $profile->monthly_salary !== null ? (float) $profile->monthly_salary * 12 : null);
        $comparison = $this->engine->compare($sales, (int) round($expenses), $salary);
        $labels = ['eight_percent' => '8% option', 'graduated_osd' => 'Graduated, optional standard deduction', 'graduated_itemized' => 'Graduated, itemized deductions'];

        return $this->result([
            'expected_sales' => $sales,
            'expense_percent' => (int) round($expenses),
            'annual_salary' => $salary,
            'over_3m' => $comparison['over_limit'],
            'options' => array_map(fn (array $o) => [...$o, 'method' => $labels[$o['key']]], $comparison['options']),
            'lowest' => $comparison['best'] !== null ? $labels[$comparison['best']] : null,
            'savings_against_the_next' => $comparison['savings'],
        ], array_map(fn (array $o) => [$labels[$o['key']].' total', $o['available'] ? $this->peso($o['total']) : 'not available'], $comparison['options']));
    }

    /**
     * The letters added, with what the app's check found; not their notes,
     * which are the taxpayer's own words.
     *
     * @return Result
     */
    private function letters(TaxProfile $profile): array
    {
        $kinds = (array) config('letters.kinds');
        $letters = Letter::query()->latest('dated_on')->latest('id')->limit(10)->get()->map(function (Letter $letter) use ($kinds, $profile) {
            $check = $this->letterCheck->for($letter, $profile);

            return [
                'kind' => $kinds[$letter->kind]['label'] ?? $letter->kind,
                'dated_on' => $letter->dated_on?->toDateString(),
                'office' => $letter->office,
                'return' => $letter->form,
                'period' => $this->letterCheck->period($letter),
                'reply_by' => $letter->reply_by?->toDateString(),
                'resolved_on' => $letter->resolved_on?->toDateString(),
                'what_the_app_found' => ['result' => $check['result'], 'title' => $check['title'], 'detail' => $check['detail']],
            ];
        })->all();
        $open = collect($letters)->whereNull('resolved_on')->count();

        return $this->result(['letters' => $letters], $letters === [] ? [] : [['Letters not yet resolved', (string) $open]]);
    }

    /**
     * @return Result
     */
    private function ruleList(): array
    {
        $rules = [];

        foreach ((array) config('tax.rules') as $id => $rule) {
            $rules[] = [
                'id' => $id,
                'title' => $rule['title'],
                'basis' => $rule['basis'],
                'effective' => $rule['effective'],
                'reviewed_by_a_cpa' => RuleReview::signOff($rule['reviewed'] ?? null) !== null,
            ];
        }

        return $this->result(['rule_pack' => config('tax.version'), 'rules' => $rules], []);
    }

    private function method(?string $regime, ?string $deduction): ?string
    {
        return match (true) {
            $regime === 'eight_percent' => '8% option',
            $regime === 'graduated' && $deduction === 'itemized' => 'Graduated rates, itemized deductions',
            $regime === 'graduated' => 'Graduated rates, optional standard deduction (40%)',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function year(array $input): int
    {
        $year = $input['year'] ?? null;
        $first = $this->rules->firstDate()->year;
        $last = CarbonImmutable::now()->year + 1;

        if (! is_int($year) || $year < $first || $year > $last) {
            throw new InvalidArgumentException("The year must be a whole number from {$first} to {$last}.");
        }

        return $year;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function quarter(array $input): int
    {
        $quarter = $input['quarter'] ?? null;

        if (! is_int($quarter) || $quarter < 1 || $quarter > 4) {
            throw new InvalidArgumentException('The quarter must be 1, 2, 3, or 4 for the annual return.');
        }

        return $quarter;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function number(array $input, string $key, float $min, float $max): ?float
    {
        $value = $input[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (! is_int($value) && ! is_float($value) || $value < $min || $value > $max) {
            throw new InvalidArgumentException("{$key} must be a number from {$min} to {$max}.");
        }

        return (float) $value;
    }

    private function peso(float $amount): string
    {
        return '₱'.number_format($amount, 2);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<Figure>  $figures
     * @return Result
     */
    private function result(array $data, array $figures): array
    {
        return ['content' => (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'figures' => $figures];
    }
}
