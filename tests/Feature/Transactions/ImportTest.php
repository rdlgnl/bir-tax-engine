<?php

use App\Notices\Models\Notice;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    Storage::fake('local');
    $this->seed(DemoTaxpayerSeeder::class);
});

/** A bank statement for Juan's October, one line of each kind. */
function juansStatement(): UploadedFile
{
    return UploadedFile::fake()->createWithContent('statement-oct-2026.csv', implode("\n", [
        'Date,Description,Amount',
        '2026-10-22,INSTAPAY LUMEN DIGITAL INC,28500',   // Oct 20: ₱30,000 less ₱1,500 withheld, already recorded
        '2026-10-23,INSTAPAY ACME CO,19000',             // Acme withholds 5%: billed ₱20,000
        '2026-10-24,PAYPAL TRANSFER UPWORK,41200',       // a payout platform
        '2026-10-25,TRANSFER FROM J. CRUZ,12500',        // only Juan knows
        '2026-10-05,MERALCO BILLS PAYMENT,-4812',        // money out
        '2026-10-26,INTEREST EARNED,12.34',              // taxed by the bank already
        '2026-03-10,INSTAPAY STUDIO UNO,5000',           // Q1 is filed
        '2026-11-05,INSTAPAY KAPE COLLECTIVE,1000',      // not yet happened
    ]));
}

test('a statement’s lines are sorted, and payments matched to clients', function () {
    $this->post('/transactions/import', ['statement' => juansStatement()])->assertRedirect('/transactions/import');

    $this->get('/transactions/import')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('transactions/import')
        ->where('statement.name', 'statement-oct-2026.csv')
        ->where('statement.matched', true)
        ->where('statement.lines', 8)
        ->where('summary', [
            'payments' => 2, 'total' => 61200, 'clients' => 2, 'ignored' => 2,
            'duplicates' => 1, 'answers' => 1, 'filed' => 1, 'invalid' => 1,
        ])
        ->where('rows.0.status', 'duplicate')
        ->where('rows.0.client', 'Lumen Digital')
        ->where('rows.1', [
            'index' => 1, 'date' => '2026-10-23', 'description' => 'INSTAPAY ACME CO', 'received' => 19000,
            'status' => 'import', 'client' => 'Acme Co.', 'amount' => 20000, 'withheld' => 1000,
            'reason' => 'Acme Co. usually withholds 5%',
        ])
        ->where('rows.2.client', 'Upwork')
        ->where('rows.3.status', 'answer')
        ->where('rows.4.status', 'ignore')
        ->where('rows.5.status', 'ignore')
        ->where('rows.6.status', 'filed')
        ->where('rows.7.status', 'invalid'));
});

test('importing records the payments, with the answers given', function () {
    $this->post('/transactions/import', ['statement' => juansStatement()]);

    $this->post('/transactions/import/confirm', ['answers' => [3 => ['kind' => 'payment', 'client' => 'Juan Cruz']]])
        ->assertRedirect('/transactions');

    $acme = Transaction::query()->where('counterparty', 'Acme Co.')->latest('id')->first();
    expect($acme->amount)->toBe('20000.00')
        ->and($acme->tax_withheld)->toBe('1000.00')
        ->and($acme->certificate_received_on)->toBeNull()
        ->and(Transaction::query()->where('counterparty', 'Upwork')->sole()->amount)->toBe('41200.00')
        ->and(Transaction::query()->where('counterparty', 'Juan Cruz')->sole()->amount)->toBe('12500.00')
        ->and(Notice::query()->where('kind', 'import')->sole()->title)->toBe('Imported 3 payments · ₱73,700')
        ->and(Storage::disk('local')->exists('imports/pending.csv'))->toBeFalse();
});

test('a line answered as personal is left out', function () {
    $this->post('/transactions/import', ['statement' => juansStatement()]);

    $this->post('/transactions/import/confirm', ['answers' => [3 => ['kind' => 'personal']]]);

    expect(Transaction::query()->where('description', 'like', 'Imported:%')->count())->toBe(2);
});

test('a client payment needs the client', function () {
    $this->post('/transactions/import', ['statement' => juansStatement()]);

    $this->post('/transactions/import/confirm', ['answers' => [3 => ['kind' => 'payment', 'client' => '']]])
        ->assertSessionHasErrors(['answers.3.client' => 'Who paid you?']);
});

test('money in and out columns, semicolons, US dates and pesos with commas are read', function () {
    $file = UploadedFile::fake()->createWithContent('bpi.csv', implode("\n", [
        'Account statement',
        'Posting Date;Particulars;Debit;Credit;Balance',
        '10/23/2026;INSTAPAY ACME CO;;₱19,000.00;50,000.00',
        '10/05/2026;MERALCO;4,812.00;;31,000.00',
    ]));
    $this->post('/transactions/import', ['statement' => $file]);

    $this->get('/transactions/import')->assertInertia(fn (Assert $page) => $page
        ->where('statement.mapping', ['date' => 0, 'description' => 1, 'amount' => null, 'credit' => 3, 'debit' => 2])
        ->where('rows.0.date', '2026-10-23')
        ->where('rows.0.received', 19000)
        ->where('rows.0.status', 'import')
        ->where('rows.1.received', -4812)
        ->where('rows.1.status', 'ignore'));
});

test('when the column names aren’t known, you choose them', function () {
    $file = UploadedFile::fake()->createWithContent('gcash.csv', implode("\n", [
        'When,What,How much',
        '2026-10-23,INSTAPAY ACME CO,19000',
    ]));
    $this->post('/transactions/import', ['statement' => $file]);
    $this->get('/transactions/import')->assertInertia(fn (Assert $page) => $page
        ->where('statement.matched', false)
        ->where('statement.columns', ['When', 'What', 'How much']));

    $this->put('/transactions/import/columns', ['date' => 0, 'description' => 1])->assertSessionHasErrors('amount');
    $this->put('/transactions/import/columns', ['date' => 0, 'description' => 1, 'amount' => 2])->assertSessionHasNoErrors();

    $this->get('/transactions/import')->assertInertia(fn (Assert $page) => $page
        ->where('statement.matched', true)
        ->where('summary.payments', 1));
});

test('only CSV statements are read, and cancelling forgets the file', function () {
    $this->post('/transactions/import', ['statement' => UploadedFile::fake()->create('statement.pdf', 10, 'application/pdf')])
        ->assertSessionHasErrors('statement');

    $this->post('/transactions/import', ['statement' => juansStatement()]);
    $this->delete('/transactions/import')->assertRedirect('/transactions/import');

    expect(Storage::disk('local')->allFiles('imports'))->toBe([]);
    $this->get('/transactions/import')->assertInertia(fn (Assert $page) => $page->where('statement', null));
});
