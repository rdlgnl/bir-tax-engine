<?php

use App\Compliance\Models\Filing;
use App\Profiles\Models\TaxProfile;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-03-10 09:00'));
});

test('a new user sees the welcome screen', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('welcome')->where('setupStarted', false));
});

test('the app is closed until setup is finished', function (string $url) {
    TaxProfile::factory()->incomplete()->create();

    $this->get($url)->assertRedirect(route('welcome'));
})->with(['/home', '/transactions', '/tax', '/tax/compare', '/profile']);

test('a finished setup goes straight to home', function () {
    TaxProfile::factory()->create();

    $this->get('/')->assertRedirect(route('home'));
});

test('someone can answer every step and reach the dashboard', function () {
    $answers = [
        'income' => ['income_source' => 'business'],
        'work' => ['work_type' => 'services', 'profession' => 'none'],
        'about' => ['full_name' => 'Juan Dela Cruz', 'bir_registered' => true],
        'certificate' => [
            'tin' => '123456789000', 'rdo_code' => '028', 'registered_on' => '2024-03-04',
            'registered_income_tax' => true, 'registered_percentage_tax' => true,
            'vat_registered' => false, 'withholding_agent' => false,
        ],
        'estimate' => ['expected_income' => 800000],
        'method' => ['regime' => 'eight_percent'],
        'withholding' => ['clients_withhold' => 'yes'],
    ];

    $next = ['work', 'about', 'certificate', 'estimate', 'method', 'withholding', 'summary'];

    foreach (array_keys($answers) as $i => $step) {
        $this->put("/setup/{$step}", $answers[$step])->assertRedirect(route('setup.show', $next[$i]));
    }

    $this->post('/setup/complete')->assertRedirect(route('home'));

    $profile = TaxProfile::current();
    expect($profile->isComplete())->toBeTrue()
        ->and($profile->tin)->toBe('123-456-789-000')
        ->and($profile->regime->value)->toBe('eight_percent')
        ->and($profile->deduction_method)->toBeNull();
});

test('the method step shows both options for the expected income', function () {
    TaxProfile::factory()->incomplete()->create(['regime' => null, 'expected_income' => 800000]);

    $this->get('/setup/method')->assertInertia(fn (Assert $page) => $page
        ->component('setup/show')
        ->where('step', 'method')
        ->where('comparison.options.0.total', 44000)
        ->where('comparison.options.1.total', 62500)
        ->where('comparison.best', 'eight_percent'));
});

test('the expenses step only appears on graduated rates', function () {
    TaxProfile::factory()->incomplete()->create();

    $this->get('/setup/expenses')->assertRedirect(route('setup.show', 'withholding'));

    $this->put('/setup/method', ['regime' => 'graduated'])->assertRedirect(route('setup.show', 'expenses'));
});

test('salaried people skip the business questions', function () {
    $this->put('/setup/income', ['income_source' => 'employed'])->assertRedirect(route('setup.show', 'about'));
    $this->put('/setup/about', ['full_name' => 'Ana Reyes', 'bir_registered' => true])
        ->assertRedirect(route('setup.show', 'summary'));
    $this->post('/setup/complete')->assertRedirect(route('home'));
});

test('each step validates its own answers', function (string $step, array $data, string $field) {
    TaxProfile::factory()->incomplete()->create();

    $this->put("/setup/{$step}", $data)->assertSessionHasErrors($field);
})->with([
    'no income source' => ['income', [], 'income_source'],
    'unknown income source' => ['income', ['income_source' => 'lottery'], 'income_source'],
    'no name' => ['about', ['bir_registered' => true], 'full_name'],
    'malformed TIN' => ['certificate', ['tin' => '12-34', 'rdo_code' => '028', 'registered_income_tax' => true, 'registered_percentage_tax' => false, 'vat_registered' => false, 'withholding_agent' => false], 'tin'],
    'negative income' => ['estimate', ['expected_income' => -5], 'expected_income'],
]);

test('8% is refused to VAT-registered taxpayers', function () {
    TaxProfile::factory()->incomplete()->create(['vat_registered' => true, 'regime' => null]);

    $this->put('/setup/method', ['regime' => 'eight_percent'])
        ->assertSessionHasErrors(['regime' => 'The 8% option is not available to VAT-registered taxpayers.']);
});

test('8% is refused above ₱3,000,000 expected sales', function () {
    TaxProfile::factory()->incomplete()->create(['expected_income' => 3_500_000, 'regime' => null]);

    $this->put('/setup/method', ['regime' => 'eight_percent'])->assertSessionHasErrors('regime');
});

test('the tax method locks once Q1 is filed', function () {
    $this->travelTo(CarbonImmutable::parse('2026-06-01'));
    TaxProfile::factory()->create();
    Filing::factory()->quarter(1, '2026-05-12', 0)->create();

    $this->put('/setup/method', ['regime' => 'graduated'])
        ->assertSessionHasErrors(['regime' => 'Your tax method for 2026 is locked: you filed Q1 on May 12.']);

    expect(TaxProfile::current()->regime->value)->toBe('eight_percent');
});

test('completing setup sends people back to an unanswered step', function () {
    TaxProfile::factory()->incomplete()->create(['clients_withhold' => null]);

    $this->post('/setup/complete')
        ->assertRedirect(route('setup.show', 'withholding'))
        ->assertSessionHasErrors('setup');
});

test('the summary explains the classification', function () {
    TaxProfile::factory()->incomplete()->create();

    $this->get('/setup/summary')->assertInertia(fn (Assert $page) => $page
        ->where('profile.regime_label', 'Flat 8%')
        ->where('profile.classification.0.label', 'Individual, self-employed')
        ->where('profile.todos.1.label', 'Update your COR'));
});
