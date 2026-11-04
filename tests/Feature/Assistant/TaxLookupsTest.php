<?php

use App\Assistant\Services\TaxLookups;
use App\Profiles\Models\TaxProfile;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-04 10:00'));
    TaxProfile::factory()->create(['full_name' => 'Maria Dela Cruz', 'tin' => '123-456-789-000', 'expected_income' => 1_200_000, 'expected_expense_percent' => 20]);
    Transaction::factory()->on('2026-02-10')->create(['counterparty' => 'Lumen Digital', 'amount' => 300_000]);
    Transaction::factory()->on('2026-08-10')->withheld(5_000, '2026-09-15')->create(['counterparty' => 'Acme Corp.', 'amount' => 100_000]);
    $this->lookups = app(TaxLookups::class);
});

/** @return array<string, mixed> */
function lookUp(string $name, array $input = []): array
{
    $result = app(TaxLookups::class)->run($name, $input);

    return ['data' => json_decode($result['content'], true), 'figures' => $result['figures'], 'content' => $result['content']];
}

test('a tax year has the method, registrations and each return, never who the taxpayer is', function () {
    ['data' => $year, 'figures' => $figures, 'content' => $content] = lookUp('tax_year', ['year' => 2026]);

    expect($year)->toMatchArray(['year' => 2026, 'method' => '8% option', 'sales_so_far' => 400000, 'income' => 'business or profession only'])
        ->and($year['registered_for'])->toMatchArray(['income_tax' => true, 'vat' => false])
        ->and($year['quarters'])->toHaveCount(4)
        ->and($year['quarters'][2])->toMatchArray(['quarter' => 3, 'return' => '1701Q', 'sales_this_quarter' => 100000, 'withheld_by_clients' => 5000])
        ->and($figures[0])->toBe(['2026 sales so far', '₱400,000.00'])
        ->and($content)->not->toContain('Maria')->not->toContain('123-456-789')->not->toContain('Lumen')->not->toContain('Acme');
});

test('a quarter’s steps name the rules they apply', function () {
    ['data' => $steps] = lookUp('quarter_steps', ['year' => 2026, 'quarter' => 3]);

    expect($steps['return'])->toBe('1701Q')
        ->and(array_column($steps['steps'], 'rule'))->toContain('IT-8PCT-PURE')
        ->and(collect($steps['steps'])->firstWhere('rule', 'IT-8PCT-PURE')['rule_title'])->toBe('8% income tax, purely self-employed');
});

test('deadlines list what is coming and what is overdue', function () {
    ['data' => $deadlines, 'figures' => $figures] = lookUp('deadlines');

    expect($deadlines['today'])->toBe('2026-11-04')
        ->and($deadlines['deadlines'])->not->toBeEmpty()
        ->and(collect($deadlines['deadlines'])->every(fn (array $d) => $d['status'] === 'overdue' || $d['date'] >= '2026-11-04'))->toBeTrue()
        ->and($figures[0][0])->toStartWith('Next: ');
});

test('the methods are compared from the profile unless told otherwise', function () {
    ['data' => $fromProfile] = lookUp('compare_methods');
    ['data' => $given, 'figures' => $figures] = lookUp('compare_methods', ['expected_sales' => 2_000_000, 'expense_percent' => 50]);

    expect($fromProfile)->toMatchArray(['expected_sales' => 1200000, 'expense_percent' => 20])
        ->and(array_column($fromProfile['options'], 'method'))->toBe(['8% option', 'Graduated, optional standard deduction', 'Graduated, itemized deductions'])
        ->and($given)->toMatchArray(['expected_sales' => 2000000, 'expense_percent' => 50])
        ->and($figures)->toHaveCount(3);
});

test('the rules list says which a CPA has reviewed', function () {
    ['data' => $rules] = lookUp('rules');

    expect($rules['rule_pack'])->toBe(config('tax.version'))
        ->and(collect($rules['rules'])->firstWhere('id', 'IT-8PCT-PURE'))->toMatchArray(['title' => '8% income tax, purely self-employed', 'reviewed_by_a_cpa' => false]);
});

test('a lookup that does not exist, or a year or quarter out of range, is refused', function (string $name, array $input, string $message) {
    expect(fn () => $this->lookups->run($name, $input))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'unknown lookup' => ['delete_everything', [], "There's no lookup called delete_everything."],
    'year before the rules' => ['tax_year', ['year' => 2015], 'The year must be a whole number from 2018 to 2027.'],
    'year as text' => ['tax_year', ['year' => '2026'], 'The year must be a whole number'],
    'quarter 5' => ['quarter_steps', ['year' => 2026, 'quarter' => 5], 'The quarter must be 1, 2, 3, or 4'],
    'negative sales' => ['compare_methods', ['expected_sales' => -1], 'expected_sales must be a number'],
]);
