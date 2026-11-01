<?php

use App\Notices\Models\Notice;
use App\Transactions\Enums\TransactionType;
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

/** A Shopee payout report: Q3's orders, one from filed Q1, one not yet happened. */
function shopeeReport(): UploadedFile
{
    return UploadedFile::fake()->createWithContent('payouts-jul-sep-2026.csv', implode("\n", [
        'Shopee Seller Centre - Income Report',
        'Order ID,Order Date,Order Amount,Commission Fee,Withholding Tax,Payout ID',
        'SH-1001,2026-07-12,"50,000.00",-3000,-250,P-01',
        'SH-1002,2026-08-03,60000,-3600,-300,P-02',
        'SH-1003,2026-09-21,90000,-5400,-450,P-03',
        'SH-0900,2026-03-05,10000,-600,-50,P-00',
        'SH-1004,2026-11-15,8000,-480,-40,P-04',
    ]));
}

test('a payout report is read into sales, fees and the platform’s withholding', function () {
    $this->post('/transactions/import/marketplace', ['platform' => 'Shopee', 'report' => shopeeReport()])
        ->assertRedirect('/transactions/import/marketplace');

    $this->get('/transactions/import/marketplace')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('transactions/marketplace')
        ->where('report.platform', 'Shopee')
        ->where('report.matched', true)
        ->where('report.from', '2026-03-05')
        ->where('summary', [
            'orders' => 4, 'payouts' => 4, 'sales' => 210000, 'fees' => 12600, 'withheld' => 1050, 'paid_out' => 196350,
            'to_import' => 3, 'duplicates' => 0, 'filed' => 1, 'invalid' => 1,
            'import_sales' => 200000, 'import_fees' => 12000, 'import_withheld' => 1000,
        ]));
});

test('each order is a sale with its withholding, and each month’s fees an expense', function () {
    $this->post('/transactions/import/marketplace', ['platform' => 'Shopee', 'report' => shopeeReport()]);
    $before = Transaction::query()->count();

    $this->post('/transactions/import/marketplace/confirm')->assertRedirect('/transactions');

    $sales = Transaction::query()->where('counterparty', 'Shopee')->where('type', TransactionType::Payment)->orderBy('occurred_on')->get();
    $fees = Transaction::query()->where('counterparty', 'Shopee')->where('type', TransactionType::Expense)->orderBy('occurred_on')->get();

    expect(Transaction::query()->count())->toBe($before + 6)
        ->and($sales->pluck('invoice_number')->all())->toBe(['SH-1001', 'SH-1002', 'SH-1003'])
        ->and($sales->pluck('amount')->all())->toBe(['50000.00', '60000.00', '90000.00'])
        ->and($sales->pluck('tax_withheld')->all())->toBe(['250.00', '300.00', '450.00'])
        ->and($fees->pluck('amount')->all())->toBe(['3000.00', '3600.00', '5400.00'])
        ->and($fees->first()->description)->toBe('Platform fees and commissions · July 2026')
        ->and($fees->first()->occurred_on->toDateString())->toBe('2026-07-12')
        // The withholding waits for Shopee's 2307, like a client's.
        ->and($sales->first()->certificate_received_on)->toBeNull()
        ->and(Notice::query()->where('kind', 'import')->sole()->title)->toBe('Imported 3 Shopee orders · ₱200,000');

    Storage::disk('local')->assertMissing('imports/marketplace.csv');

    // The same report again: every order is already recorded.
    $this->post('/transactions/import/marketplace', ['platform' => 'Shopee', 'report' => shopeeReport()]);
    $this->get('/transactions/import/marketplace')->assertInertia(fn (Assert $page) => $page
        ->where('summary.to_import', 0)
        ->where('summary.duplicates', 3));
    $this->post('/transactions/import/marketplace/confirm');

    expect(Transaction::query()->count())->toBe($before + 6);
});

test('columns it can’t name are chosen by hand', function () {
    $file = UploadedFile::fake()->createWithContent('lazada.csv', implode("\n", [
        'Ref,When,Buyer total,Charges,Tax',
        'LZ-1,2026-09-02,12000,720,60',
    ]));

    $this->post('/transactions/import/marketplace', ['platform' => 'Lazada', 'report' => $file]);
    $this->get('/transactions/import/marketplace')->assertInertia(fn (Assert $page) => $page
        ->where('report.matched', false)
        ->where('summary', null));

    $this->put('/transactions/import/marketplace/columns', ['date' => 1, 'sales' => 1])->assertSessionHasErrors('sales');
    $this->put('/transactions/import/marketplace/columns', ['date' => 1, 'sales' => 2, 'order' => 0, 'fees' => 3, 'withheld' => 4])
        ->assertSessionHasNoErrors();

    $this->get('/transactions/import/marketplace')->assertInertia(fn (Assert $page) => $page
        ->where('report.matched', true)
        ->where('summary.import_sales', 12000)
        ->where('summary.import_withheld', 60));
});

test('a file with no orders is turned away, and cancelling removes it', function () {
    $this->post('/transactions/import/marketplace', ['platform' => 'Shopee', 'report' => UploadedFile::fake()->createWithContent('empty.csv', '')])
        ->assertSessionHasErrors('report');
    $this->post('/transactions/import/marketplace', ['platform' => '', 'report' => shopeeReport()])->assertSessionHasErrors('platform');

    $this->post('/transactions/import/marketplace', ['platform' => 'Shopee', 'report' => shopeeReport()]);
    $this->delete('/transactions/import/marketplace')->assertRedirect('/transactions/import/marketplace');

    Storage::disk('local')->assertMissing('imports/marketplace.csv');
    $this->get('/transactions/import/marketplace')->assertInertia(fn (Assert $page) => $page->where('report', null));
});
