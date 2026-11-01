<?php

use App\Books\Services\GeneralLedger;
use App\Returns\Models\Filing;
use App\Transactions\Enums\ExpenseCategory;
use App\Transactions\Enums\VatKind;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
});

/**
 * @return list<array{0: int, 1: float, 2: float}>
 */
function lines(array $entry): array
{
    return array_map(fn (array $line) => [$line['account'], $line['debit'], $line['credit']], $entry['lines']);
}

test('October’s receipts and expenses are double entries, like the cash journals', function () {
    $journal = app(GeneralLedger::class)->journal(2026, 10);

    expect(array_column($journal, 'ref'))->toBe(['GJ-2026-10-001', 'GJ-2026-10-002', 'GJ-2026-10-003'])
        ->and(array_column($journal, 'description'))->toBe([
            'Received from Studio Uno · invoice 0140',
            'Internet & phone · Converge ICT',
            'Received from Lumen Digital · invoice 0141',
        ])
        ->and(lines($journal[0]))->toBe([[101, 25000.0, 0.0], [401, 0.0, 25000.0]])
        ->and(lines($journal[1]))->toBe([[510, 3500.0, 0.0], [101, 0.0, 3500.0]])
        ->and(lines($journal[2]))->toBe([[101, 28500.0, 0.0], [105, 1500.0, 0.0], [401, 0.0, 30000.0]]);
});

test('VAT, the taxpayer’s own withholding and tax payments each go to their account', function () {
    Transaction::factory()->on('2026-10-05')->create(['counterparty' => 'Big Client', 'amount' => 100_000, 'tax_withheld' => 2_000, 'vat_kind' => VatKind::Vatable, 'vat_amount' => 12_000]);
    Transaction::factory()->expense()->on('2026-10-06')->create(['counterparty' => 'Ana Reyes', 'category' => ExpenseCategory::Person, 'amount' => 20_000, 'agent_withheld' => 2_000]);
    Filing::factory()->create(['year' => 2026, 'quarter' => 3, 'form' => '2551Q', 'filed_on' => '2026-10-24', 'paid_on' => '2026-10-24', 'amount_paid' => 4_500, 'snapshot' => ['penalties' => ['total' => 500]]]);
    Filing::factory()->create(['year' => 2026, 'quarter' => 3, 'month' => 9, 'form' => '0619-E', 'filed_on' => '2026-10-09', 'paid_on' => '2026-10-09', 'amount_paid' => 2_000]);
    // Filed but not paid yet: not in the books.
    Filing::factory()->create(['year' => 2026, 'quarter' => 3, 'form' => '2550Q', 'filed_on' => '2026-10-20', 'paid_on' => null, 'amount_paid' => 9_000]);

    $journal = collect(app(GeneralLedger::class)->journal(2026, 10))->keyBy('description');

    expect(lines($journal['Received from Big Client']))->toBe([[101, 110000.0, 0.0], [105, 2000.0, 0.0], [401, 0.0, 100000.0], [205, 0.0, 12000.0]])
        ->and(lines($journal['Paid a person · Ana Reyes']))->toBe([[515, 20000.0, 0.0], [101, 0.0, 18000.0], [201, 0.0, 2000.0]])
        ->and(lines($journal['2551Q paid · Q3 2026']))->toBe([[520, 4000.0, 0.0], [521, 500.0, 0.0], [101, 0.0, 4500.0]])
        ->and(lines($journal['0619-E paid · September 2026']))->toBe([[201, 2000.0, 0.0], [101, 0.0, 2000.0]])
        ->and($journal->keys())->not->toContain('2550Q paid · Q3 2026');

    foreach ($journal as $entry) {
        expect(array_sum(array_column($entry['lines'], 'debit')))->toBe(array_sum(array_column($entry['lines'], 'credit')));
    }
});

test('the ledger carries each account’s balance from January 1 through the month', function () {
    $ledger = app(GeneralLedger::class);
    $september = collect($ledger->ledger(2026, 9)['accounts'])->keyBy('code');
    $october = $ledger->ledger(2026, 10);
    $accounts = collect($october['accounts'])->keyBy('code');

    expect($october['debits'])->toBe($october['credits'])
        ->and($accounts[101]['opening'])->toBe($september[101]['closing'])
        ->and($accounts[101]['closing'])->toBe(round($september[101]['closing'] + 25_000 - 3_500 + 28_500, 2))
        ->and($accounts[401]['credits'])->toBe(55000.0)
        ->and($accounts[510]['postings'][0])->toMatchArray(['ref' => 'GJ-2026-10-002', 'debit' => 3500.0, 'balance' => $accounts[510]['closing']]);
});

test('the general journal and ledger page and PDF', function () {
    $this->get('/books/2026/10/general')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('books/general')
        ->where('period', ['year' => 2026, 'month' => 10])
        ->where('next', null)
        ->has('journal', 3)
        ->where('accounts.101', 'Cash')
        ->where('ledger.debits', 58500));

    $response = $this->get('/books/2026/10/general/pdf')->assertOk();
    expect($response->headers->get('content-type'))->toBe('application/pdf')
        ->and($response->headers->get('content-disposition'))->toContain('general-journal-ledger-2026-10.pdf');

    $this->get('/books/2026/11/general')->assertNotFound();
});
