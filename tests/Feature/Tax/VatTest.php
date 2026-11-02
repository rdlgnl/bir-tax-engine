<?php

use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\OfficialForms;
use App\Returns\Services\ReturnForm;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Services\VatCalculator;
use App\Transactions\Enums\TransactionType;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-20 10:00'));
});

/** Dante from the design: VAT-registered, graduated with OSD, Q3 2026. */
function vatTaxpayer(): TaxProfile
{
    $profile = TaxProfile::factory()->graduated(DeductionMethod::Osd)->create([
        'vat_registered' => true, 'registered_percentage_tax' => false, 'profession' => 'cpa_lawyer',
    ]);

    $sale = fn (string $date, string $client, float $amount, string $tin) => Transaction::query()->create([
        'type' => TransactionType::Payment, 'occurred_on' => $date, 'counterparty' => $client, 'counterparty_tin' => $tin,
        'amount' => $amount, 'vat_kind' => 'vatable', 'vat_amount' => round($amount * 0.12, 2),
    ]);
    $purchase = fn (string $date, string $supplier, float $amount, bool $inMyName) => Transaction::query()->create([
        'type' => TransactionType::Expense, 'occurred_on' => $date, 'counterparty' => $supplier, 'category' => 'software',
        'amount' => $amount, 'vat_amount' => round($amount * 0.12, 2), 'has_valid_invoice' => $inMyName,
    ]);

    $sale('2026-07-10', 'Lumen Digital Inc.', 240_000, '008-123-456-000');
    $sale('2026-08-12', 'Northbay Media', 180_000, '');
    $purchase('2026-08-01', 'Cloud tools reseller', 50_000, true);
    $purchase('2026-09-01', 'Office space', 40_000, true);
    $purchase('2026-09-15', 'Lunch with client', 5_000, false);

    return $profile;
}

test('Q3 VAT is the VAT on sales less the claimable VAT on purchases', function () {
    vatTaxpayer();

    expect(app(VatCalculator::class)->forYear(2026)[3])->toMatchArray([
        'vatable_sales' => 420000.0, 'output_vat' => 50400.0,
        'purchases' => 90000.0, 'input_vat' => 10800.0,
        'excluded_count' => 1, 'excluded_vat' => 600.0,
        'carried_over' => 0.0, 'payable' => 39600.0, 'excess' => 0.0,
        'due_date' => '2026-10-26', 'status' => 'due',
    ]);
});

test('excess input VAT carries over to the next quarter', function () {
    vatTaxpayer();
    Transaction::query()->create([
        'type' => TransactionType::Expense, 'occurred_on' => '2026-05-10', 'counterparty' => 'Laptop store', 'category' => 'equipment',
        'amount' => 100_000, 'vat_amount' => 12_000, 'has_valid_invoice' => true,
    ]);

    $quarters = app(VatCalculator::class)->forYear(2026);

    expect($quarters[2])->toMatchArray(['payable' => 0.0, 'excess' => 12000.0])
        ->and($quarters[3])->toMatchArray(['carried_over' => 12000.0, 'payable' => 27600.0]);
});

test('excess input VAT left after Q4 carries into the next year (NIRC Sec. 110(B))', function () {
    // A VAT-registered year ending with more VAT on purchases than on sales.
    Transaction::factory()->expense(validInvoice: true)->on('2025-11-10')->create(['counterparty' => 'Big Supplier', 'amount' => 200_000, 'vat_amount' => 24_000]);

    $previous = app(VatCalculator::class)->forYear(2025);
    $this_year = app(VatCalculator::class)->forYear(2026);

    expect($previous[4]['excess'])->toBe(24000.0)
        ->and($this_year[1]['carried_over'])->toBe(24000.0);
});

test('a VAT-registered taxpayer records VAT; others don’t', function () {
    vatTaxpayer();

    $this->post('/transactions', [
        'type' => 'payment', 'occurred_on' => '2026-10-15', 'counterparty' => 'Northbay Media', 'counterparty_tin' => '123-456-789-000',
        'amount' => '50000', 'tax_withheld' => '0', 'vat_kind' => 'vatable', 'vat_amount' => '6000',
    ])->assertSessionHasNoErrors();
    $this->post('/transactions', [
        'type' => 'payment', 'occurred_on' => '2026-10-16', 'counterparty' => 'Overseas Co', 'amount' => '30000',
        'tax_withheld' => '0', 'vat_kind' => 'zero_rated', 'vat_amount' => '3600',
    ])->assertSessionHasNoErrors();

    expect(Transaction::query()->where('counterparty', 'Northbay Media')->latest('id')->first())
        ->vat_amount->toBe('6000.00')->counterparty_tin->toBe('123-456-789-000')
        ->and(Transaction::query()->where('counterparty', 'Overseas Co')->sole()->vat_amount)->toBe('0.00');

    TaxProfile::current()->update(['vat_registered' => false]);
    $this->post('/transactions', [
        'type' => 'payment', 'occurred_on' => '2026-10-17', 'counterparty' => 'Kape', 'amount' => '1000',
        'tax_withheld' => '0', 'vat_kind' => 'vatable', 'vat_amount' => '120',
    ]);

    expect(Transaction::query()->where('counterparty', 'Kape')->sole())->vat_kind->toBeNull()->vat_amount->toBe('0.00');
});

test('the 2550Q is prepared like the other returns, and listed with them', function () {
    vatTaxpayer();

    $this->get('/file/2026/3/2550Q')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('returns/show')
        ->where('layout.title', 'Quarterly Value-Added Tax Return')
        ->where('layout.method', '12% VAT')
        ->where('layout.payable', 39600)
        ->where('layout.taxpayer.atc', 'VT010')
        ->where('layout.lines.0', ['label' => 'VATable sales', 'amount' => 420000, 'kind' => 'amount'])
        ->where('dueDate', '2026-10-26'));

    $this->get('/history')->assertInertia(fn (Assert $page) => $page
        ->where('entries', fn ($entries) => collect($entries)->contains(fn ($e) => $e['form'] === '2550Q' && $e['quarter'] === 3
            && $e['title'] === 'Q3 · VAT' && (float) $e['amount'] === 39600.0 && $e['due_date'] === '2026-10-26' && $e['status'] === 'due')));

    $this->get('/home')->assertInertia(fn (Assert $page) => $page
        ->where('vat.current.quarter', 4)
        ->where('vat.previous.payable', 39600)
        ->where('upcoming', fn ($items) => collect($items)->contains(fn ($i) => $i['title'] === 'Q3 2550Q · VAT' && $i['href'] === '/file/2026/3/2550Q')));
});

test('the filled 2550Q is the BIR form, with the VAT computation', function () {
    $profile = vatTaxpayer();
    $snapshot = app(ReturnForm::class)->snapshot($profile, 2026, 3, '2550Q');

    expect(app(OfficialForms::class)->values($snapshot))->toMatchArray([
        '1.calendar' => true, '2' => '122026', '3.3' => true, '4.from' => '07012026', '4.to' => '09302026',
        '13.micro' => true,
        '31A' => 420000.0, '31B' => 50400.0, '34A' => 420000.0, '37B' => 50400.0,
        '44A' => 90000.0, '44B' => 10800.0, '51B' => 10800.0, '60B' => 10800.0,
        '61B' => 39600.0, '15' => 39600.0, '26' => 39600.0,
    ]);

    $pdf = $this->get('/file/2026/3/2550Q/pdf')->assertOk()->getContent();
    expect((new Fpdi)->setSourceFile(StreamReader::createByString($pdf)))->toBe(2);
});

test('the SLSP lists customers and suppliers, and exports the BIR’s files', function () {
    vatTaxpayer();

    $this->get('/file/2026/3/slsp')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('returns/slsp')
        ->where('sales.0', ['name' => 'Lumen Digital Inc.', 'tin' => '008-123-456-000', 'invoices' => 1, 'vatable' => 240000, 'zero_rated' => 0, 'exempt' => 0, 'output_vat' => 28800])
        ->where('purchases.0.name', 'Cloud tools reseller')
        ->where('excluded.0.name', 'Lunch with client')
        ->where('totals', ['vatable' => 420000, 'output_vat' => 50400, 'input_vat' => 10800, 'excluded' => 1])
        ->where('files.sales', '123456789S092026.DAT'));

    $sales = explode("\r\n", trim($this->get('/file/2026/3/slsp/sales')->assertOk()->getContent()));
    expect($sales)->toHaveCount(3)
        ->and($sales[0])->toStartWith('H,S,"123456789"')
        ->and($sales[0])->toEndWith(',0.00,0.00,420000.00,50400.00,028,09/30/2026,12')
        ->and($sales[1])->toBe('D,S,"008123456","LUMEN DIGITAL INC.","","","","","",0.00,0.00,240000.00,28800.00,123456789,09/30/2026');

    $purchases = explode("\r\n", trim($this->get('/file/2026/3/slsp/purchases')->getContent()));
    expect($purchases)->toHaveCount(3)
        ->and($purchases[1])->toContain('"CLOUD TOOLS RESELLER"');

    $this->get('/file/2026/3/slsp/csv')->assertOk()->assertHeader('Content-Type', 'text/csv; charset=utf-8');
});

test('a 2550Q can be marked filed, and its quarter shows as filed', function () {
    vatTaxpayer();

    $this->post('/filings', ['year' => 2026, 'quarter' => 3, 'form' => '2550Q', 'filed_on' => '2026-10-20', 'paid' => true, 'amount_paid' => 39600, 'paid_on' => '2026-10-20', 'payment_channel' => 'online'])
        ->assertSessionHasNoErrors();

    expect(app(VatCalculator::class)->forYear(2026)[3])->toMatchArray(['status' => 'filed', 'filed_on' => '2026-10-20', 'amount_paid' => 39600.0]);
    $this->get('/file/2026/3/2550Q')->assertRedirect('/history?year=2026&quarter=3&form=2550Q');
});

test('without VAT registration there is no 2550Q or SLSP', function () {
    TaxProfile::factory()->graduated()->create();

    $this->get('/file/2026/3/2550Q')->assertNotFound();
    $this->get('/file/2026/3/slsp')->assertNotFound();
});
