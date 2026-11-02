<?php

use App\Books\Enums\BooksType;
use App\Books\Models\BooksMonth;
use App\Profiles\Models\TaxProfile;
use App\Tax\Enums\Regime;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
});

test('October’s journals match the design', function () {
    $this->get('/books/2026/10')->assertInertia(fn (Assert $page) => $page
        ->component('books/show')
        ->where('period', ['year' => 2026, 'month' => 10])
        ->where('previous', ['year' => 2026, 'month' => 9])
        ->where('next', null)
        ->where('booksType', 'manual')
        ->where('regime', 'eight_percent')
        ->where('copiedAt', null)
        ->has('receipts', 2)
        ->where('receipts.0.counterparty', 'Studio Uno')
        ->where('receipts.0.invoice_number', '0140')
        ->where('receipts.0.received', 25000)
        ->where('receipts.1.counterparty', 'Lumen Digital')
        ->where('receipts.1.withheld', 1500)
        ->where('receipts.1.received', 28500)
        ->has('disbursements', 1)
        ->where('disbursements.0.counterparty', 'Converge ICT')
        ->where('disbursements.0.category', 'Internet & phone')
        ->where('disbursements.0.amount', 3500)
        ->where('totals', ['amount' => 55000, 'vat' => 0, 'withheld' => 1500, 'received' => 53500, 'disbursed' => 3500, 'disbursed_vat' => 0, 'disbursed_withheld' => 0, 'paid' => 3500]));
});

test('the books open on the current month', function () {
    $this->get('/books')->assertInertia(fn (Assert $page) => $page
        ->where('period', ['year' => 2026, 'month' => 10]));
});

test('an invoice enters the receipts journal once it is paid', function () {
    // The seeded Oct 26 invoice 0142 is unpaid.
    $this->get('/books/2026/10')->assertInertia(fn (Assert $page) => $page->has('receipts', 2));

    Transaction::query()->where('invoice_number', '0142')->sole()->update(['invoice_paid' => true, 'invoice_paid_on' => '2026-10-27']);

    // On the day it was paid, not the invoice's date.
    $this->get('/books/2026/10')->assertInertia(fn (Assert $page) => $page
        ->has('receipts', 3)
        ->where('receipts.2.invoice_number', '0142')
        ->where('receipts.2.occurred_on', '2026-10-27'));
});

test('months in the future or out of range aren’t books', function (string $url) {
    $this->get($url)->assertNotFound();
})->with(['/books/2026/11', '/books/2027/1', '/books/2026/13', '/books/2017/12']);

test('the books type is remembered', function () {
    $this->put('/books/type', ['books_type' => 'loose_leaf'])->assertRedirect();

    expect(TaxProfile::current()->books_type)->toBe(BooksType::LooseLeaf);

    $this->put('/books/type', ['books_type' => 'cloud'])->assertSessionHasErrors('books_type');
});

test('marking a month copied keeps the first date', function () {
    $this->post('/books/2026/9/copied')->assertRedirect();

    $this->travelTo(CarbonImmutable::parse('2026-10-29 09:00'));
    $this->post('/books/2026/9/copied')->assertRedirect();

    expect(BooksMonth::query()->count())->toBe(1);

    $this->get('/books/2026/9')->assertInertia(fn (Assert $page) => $page
        ->where('copiedAt', '2026-10-27'));
});

test('the 8% note goes away on graduated rates', function () {
    TaxProfile::current()->update(['regime' => Regime::Graduated]);

    $this->get('/books/2026/10')->assertInertia(fn (Assert $page) => $page->where('regime', 'graduated'));
});

test('the month’s books download as a page-numbered PDF', function () {
    $response = $this->get('/books/2026/10/pdf')->assertOk();

    expect($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($response->headers->get('Content-Disposition'))->toContain('books-2026-10.pdf')
        ->and(substr($response->getContent(), 0, 4))->toBe('%PDF');
});
