<?php

use App\Data\Models\Setting;
use App\Profiles\Models\TaxProfile;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-28 13:12'));
    Storage::fake('local');
    $this->seed(DemoTaxpayerSeeder::class);
    Storage::disk('local')->put('certificates/acme.pdf', '%PDF');
    Setting::put('backup_reminder', false);
});

test('the erase page says what will go', function () {
    $this->get('/data/erase')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('data/erase')
        ->where('summary', ['records' => 18, 'first_year' => 2026, 'last_year' => 2026, 'certificates' => 0, 'documents' => 1, 'filings' => 2])
        ->where('lastBackup', null)
        ->where('retentionYears', 5));
});

test('nothing is erased without typing ERASE', function (string $typed) {
    $this->delete('/data', ['confirmation' => $typed])->assertSessionHasErrors('confirmation');

    expect(Transaction::query()->count())->toBe(18);
})->with(['', 'erase', 'delete']);

test('ERASE removes every record, document and setting, back to the welcome screen', function () {
    $this->delete('/data', ['confirmation' => 'ERASE'])->assertRedirect('/');

    foreach (['tax_profiles', 'transactions', 'certificates', 'filings', 'books_months', 'app_settings'] as $table) {
        expect(DB::table($table)->count())->toBe(0, $table);
    }

    expect(Storage::disk('local')->allFiles())->toBe([])
        ->and(TaxProfile::current())->toBeNull();
    $this->get('/home')->assertRedirect('/');
});
