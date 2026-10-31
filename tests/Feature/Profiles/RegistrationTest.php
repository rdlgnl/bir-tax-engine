<?php

use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\Items\Form1905Items;
use App\Returns\Forms\OfficialFormFiller;
use App\Tax\Enums\DeductionMethod;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
});

test('on 8% with percentage tax still on the COR, removing it is suggested', function () {
    $this->seed(DemoTaxpayerSeeder::class);
    TaxProfile::current()->update(['registered_percentage_tax' => true]);

    $this->get('/profile/registration')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('profile/registration')
        ->where('corOutdated', true)
        ->where('updates.0', [
            'key' => 'remove_percentage_tax', 'label' => 'Remove percentage tax',
            'detail' => 'Because you chose 8%', 'status' => 'suggested',
        ])
        ->where('updates.1.status', 'not_needed')
        ->where('updates.1.detail', 'When sales pass ₱3,000,000')
        ->where('updates.3.status', 'orus'));
});

test('the filled 1905 is the BIR form, every page of it', function () {
    $this->seed(DemoTaxpayerSeeder::class);
    TaxProfile::current()->update(['registered_percentage_tax' => true]);

    $response = $this->get('/profile/registration/1905')->assertOk()->assertHeader('Content-Type', 'application/pdf');

    expect($response->headers->get('Content-Disposition'))->toContain('1905-2026-remove-percentage-tax.pdf')
        ->and((new Fpdi)->setSourceFile(StreamReader::createByString($response->getContent())))->toBe(4);
});

test('the 1905 cancels percentage tax from the start of the year, for the 8% option', function () {
    $this->seed(DemoTaxpayerSeeder::class);

    $values = app(Form1905Items::class)->removePercentageTax(TaxProfile::current(), 2026);

    expect($values)->toMatchArray([
        'tin.1' => '123', 'tin.2' => '456', 'tin.3' => '789', '2' => '028',
        '4' => 'DELA CRUZ, JUAN SANTOS',
        '6.eight_percent' => true, '7G' => true,
        '7G.cancelled' => 'PERCENTAGE TAX', '7G.cancelled_form' => '2551Q', '7G.cancelled_date' => '01012026',
        '12' => 'JUAN SANTOS DELA CRUZ',
    ]);

    // Every value has a box on the form.
    expect(app(OfficialFormFiller::class)->marks('1905', $values))->not->toBeEmpty();
});

test('with nothing to update there is no 1905 to download', function () {
    TaxProfile::factory()->graduated(DeductionMethod::Osd)->create(['registered_percentage_tax' => true]);

    $this->get('/profile/registration')->assertInertia(fn (Assert $page) => $page
        ->where('corOutdated', false)
        ->where('updates.0.status', 'not_needed'));
    $this->get('/profile/registration/1905')->assertNotFound();
});

test('the profile’s COR to-do links to the update once setup is done', function () {
    $this->seed(DemoTaxpayerSeeder::class);
    TaxProfile::current()->update(['registered_percentage_tax' => true]);

    $this->get('/profile')->assertInertia(fn (Assert $page) => $page
        ->where('profile.todos', fn ($todos) => collect($todos)->contains(fn ($todo) => $todo['label'] === 'Update your COR' && $todo['href'] === '/profile/registration')));
});
