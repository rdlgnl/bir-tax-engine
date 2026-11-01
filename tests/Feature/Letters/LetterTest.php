<?php

use App\Compliance\Services\TaxObligations;
use App\Data\Services\LocalData;
use App\Letters\Models\Letter;
use App\Letters\Services\LetterCheck;
use App\Profiles\Models\TaxProfile;
use App\Returns\Models\Filing;
use App\Tax\Services\TaxCalculationService;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
});

function letterCheck(array $attributes): array
{
    return app(LetterCheck::class)->for(new Letter($attributes), TaxProfile::current());
}

test('a reminder for a 2551Q in a year on 8% says it wasn’t needed (design 9-07)', function () {
    Filing::factory()->quarter(1, '2024-05-10', 0)->create(['year' => 2024, 'reference_number' => 'EBIR-2024-Q1', 'snapshot' => ['regime' => 'eight_percent']]);

    $check = letterCheck(['kind' => 'unfiled', 'form' => '2551Q', 'year' => 2024, 'quarter' => 1]);

    expect($check)->toMatchArray([
        'result' => 'not_needed',
        'title' => "Your records say you didn't need it",
        'detail' => 'In 2024 you chose 8% on your Q1 1701Q. The 8% covers percentage tax, so 2551Q wasn\'t required.',
        'url' => '/history?year=2024&quarter=1&form=1701Q',
    ])->and($check['documents'][0])->toMatchArray(['title' => 'Q1 2024 1701Q', 'detail' => 'Shows the 8% option', 'status' => 'In History'])
        ->and($check['documents'][1]['status'])->toBe('Recorded');
});

test('a return the records show filed points to the filing and its proof', function () {
    $check = letterCheck(['kind' => 'unfiled', 'form' => '1701Q', 'year' => 2026, 'quarter' => 2]);

    expect($check['result'])->toBe('filed')
        ->and($check['detail'])->toContain('filed on Aug 14, 2026 (reference EBIR-2026-Q2), and paid ₱4,000 on Aug 14, 2026')
        ->and(array_column($check['documents'], 'status'))->toBe(['In History', 'Recorded', 'Recorded']);
});

test('a return missing from the records leads to filing it', function () {
    expect(letterCheck(['kind' => 'unpaid', 'form' => '1701Q', 'year' => 2026, 'quarter' => 3]))
        ->toMatchArray(['result' => 'missing', 'url' => '/file/2026/3/1701Q'])
        ->and(letterCheck(['kind' => 'unfiled', 'form' => '1701Q', 'year' => 2022, 'quarter' => 1]))
        ->toMatchArray(['result' => 'missing', 'url' => null]);
});

test('returns for registrations the taxpayer doesn’t have weren’t needed', function () {
    expect(letterCheck(['kind' => 'unfiled', 'form' => '2550Q', 'year' => 2026, 'quarter' => 2])['detail'])->toStartWith("You aren't VAT-registered")
        ->and(letterCheck(['kind' => 'unfiled', 'form' => '1601-C', 'year' => 2026, 'month' => 5])['detail'])->toStartWith('You had no employees for May 2026')
        ->and(letterCheck(['kind' => 'unfiled', 'form' => '0619-E', 'year' => 2026, 'month' => 4])['result'])->toBe('not_needed');
});

test('a letter about income compares with what was recorded, and audits need a professional', function () {
    $income = letterCheck(['kind' => 'mismatch', 'year' => 2026, 'quarter' => 3]);
    $q3 = app(TaxCalculationService::class)->forYear(TaxProfile::current(), 2026)['quarters'][3];

    expect($income['result'])->toBe('income')
        ->and($income['detail'])->toStartWith('You recorded ₱'.number_format($q3['quarter_gross']).' in sales')
        ->and(letterCheck(['kind' => 'authority', 'year' => 2026])['result'])->toBe('none')
        ->and((new Letter(['kind' => 'authority']))->needsHelp())->toBeTrue();
});

test('a letter is saved with its photo, shown, put on the calendar and dealt with', function () {
    $this->post('/letters', ['kind' => 'nonsense'])->assertSessionHasErrors('kind');
    $this->post('/letters', ['kind' => 'unfiled', 'form' => '9999'])->assertSessionHasErrors('form');

    $this->post('/letters', [
        'kind' => 'unfiled', 'form' => '1701Q', 'year' => '2026', 'quarter' => '2', 'dated_on' => '2026-10-12',
        'office' => 'Revenue District Office 028', 'reply_by' => '2026-11-20', 'file' => UploadedFile::fake()->image('letter.jpg'),
    ])->assertSessionHasNoErrors();
    $letter = Letter::query()->sole();

    expect($letter->file_path)->toStartWith('certificates/letters/');
    Storage::disk('local')->assertExists($letter->file_path);

    $this->get("/letters/{$letter->id}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('letters/show')
        ->where('letter.kind_label', 'Reminder of a return not filed')
        ->where('letter.period', 'Q2 2026')
        ->where('check.result', 'filed'));
    $this->get("/letters/{$letter->id}/file")->assertOk();

    $profile = TaxProfile::current();
    $reminders = fn () => array_values(array_filter(
        app(TaxObligations::class)->deadlines($profile, app(TaxCalculationService::class)->forYear($profile, 2026)),
        fn (array $d) => str_starts_with($d['title'], 'Reply to the BIR'),
    ));
    expect($reminders())->toHaveCount(1)
        ->and($reminders()[0])->toMatchArray(['date' => '2026-11-20', 'title' => 'Reply to the BIR: reminder of a return not filed', 'href' => "/letters/{$letter->id}"])
        ->and(app(LocalData::class)->rows()['bir_letters'])->toHaveCount(1);

    $this->post("/letters/{$letter->id}/resolve")->assertRedirect();
    expect($letter->fresh()->resolved_on?->toDateString())->toBe('2026-10-27')
        ->and($reminders())->toBe([]);

    $this->get('/letters')->assertInertia(fn (Assert $page) => $page->has('letters', 1));
    $this->delete("/letters/{$letter->id}")->assertRedirect('/letters');
    Storage::disk('local')->assertMissing($letter->file_path);
});
