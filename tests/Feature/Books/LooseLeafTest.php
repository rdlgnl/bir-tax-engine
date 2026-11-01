<?php

use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\Items\Form1900Items;
use App\Returns\Forms\OfficialFormFiller;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
});

test('the loose-leaf page gives the steps and the rules from the rule pack', function () {
    $this->get('/books/loose-leaf')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('books/loose-leaf')
        ->where('booksType', 'manual')
        ->where('books', ['CASH RECEIPTS JOURNAL', 'CASH DISBURSEMENTS JOURNAL', 'GENERAL JOURNAL', 'GENERAL LEDGER'])
        ->where('bindWithinDays', 15)
        ->where('retentionYears', 5)
        ->where('edition', 'October 2025 (ENCS)'));
});

test('the filled 1900 asks for loose-leaf books of accounts, from the profile', function () {
    $values = app(Form1900Items::class)->looseLeafBooks(TaxProfile::current());

    expect($values)->toMatchArray([
        'tin.1' => '123', 'tin.2' => '456', 'tin.3' => '789', '2' => '028',
        '3' => 'DELA CRUZ, JUAN SANTOS',
        '7.tin' => '123-456-789', '7.branch' => '00000',
        '8.books' => true,
        '9A.1' => 'CASH RECEIPTS JOURNAL', '9A.2' => 'CASH DISBURSEMENTS JOURNAL',
        '9A.3' => 'GENERAL JOURNAL', '9A.4' => 'GENERAL LEDGER',
        '10' => 'JUAN SANTOS DELA CRUZ',
    ])
        ->and(app(OfficialFormFiller::class)->marks('1900', $values))->not->toBeEmpty();
});

test('the application downloads as the BIR’s own legal-size form', function () {
    $response = $this->get('/books/loose-leaf/application')->assertOk()->assertHeader('Content-Type', 'application/pdf');

    expect($response->headers->get('Content-Disposition'))->toContain('1900-loose-leaf-books.pdf')
        ->and($response->getContent())->toMatch('#/MediaBox \[0 0 612\.00 1008\.00\]#');
});
