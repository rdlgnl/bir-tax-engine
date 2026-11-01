<?php

use App\Compliance\Models\Certificate;
use App\Data\Controllers\DataController;
use App\Data\Models\Setting;
use App\Profiles\Models\TaxProfile;
use App\Returns\Models\Filing;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-28 13:12'));
    Storage::fake('local');
    $this->folder = storage_path('framework/testing/backups-'.uniqid());
    config(['backup.folder' => $this->folder]);
});

afterEach(function () {
    File::deleteDirectory($this->folder);
});

/** Juan's records, with one 2307 scan on disk. */
function seedWithScan(): void
{
    test()->seed(DemoTaxpayerSeeder::class);
    test()->post('/certificates', [
        'payor_name' => 'Acme Co.', 'payor_tin' => '008-123-456-000', 'year' => 2026, 'quarter' => 3,
        'amount_paid' => '20000', 'tax_withheld' => '1000', 'atc_code' => 'WI010',
        'file' => UploadedFile::fake()->createWithContent('2307_Acme_Q3.pdf', '%PDF-1.4 acme'),
    ])->assertSessionHasNoErrors();
}

function backUp(string $password = 'correct horse'): string
{
    test()->post('/data/backup', ['password' => $password, 'password_confirmation' => $password])
        ->assertSessionHasNoErrors();

    $backup = Setting::get('last_backup');

    return "{$backup['folder']}/{$backup['file']}";
}

function chooseBackup(string $path, string $name = 'buwisbuddy-2026-10-28.bbk'): void
{
    test()->post('/data/restore', ['backup' => new UploadedFile($path, $name, null, null, true)])
        ->assertSessionHasNoErrors();
}

test('a backup is written to the backup folder, encrypted, and remembered', function () {
    seedWithScan();

    $path = backUp();

    expect($path)->toEndWith('/buwisbuddy-2026-10-28.bbk')
        ->and(Setting::get('last_backup'))->toMatchArray(['file' => 'buwisbuddy-2026-10-28.bbk', 'folder' => $this->folder, 'at' => '2026-10-28T13:12:00+08:00']);

    $zip = new ZipArchive;
    $zip->open($path);
    $manifest = json_decode($zip->getFromName('manifest.json'), true);

    // What the file holds is readable without the password; the records aren't.
    expect($manifest)->toMatchArray(['app' => 'BuwisBuddy', 'format' => 1, 'rule_pack' => '2026.11'])
        ->and($manifest['summary'])->toBe(['records' => 18, 'first_year' => 2026, 'last_year' => 2026, 'certificates' => 1, 'documents' => 1, 'filings' => 2])
        ->and($zip->statName('data.json')['encryption_method'])->toBe(ZipArchive::EM_AES_256)
        ->and($zip->getFromName('data.json'))->toBeFalse()
        ->and(json_encode($manifest))->not->toContain('Juan');

    $zip->setPassword('correct horse');
    expect(json_decode($zip->getFromName('data.json'), true)['tables']['transactions'])->toHaveCount(18);
    $zip->close();
});

test('a second backup on the same day gets its own file', function () {
    $this->seed(DemoTaxpayerSeeder::class);

    backUp();

    expect(backUp())->toEndWith('/buwisbuddy-2026-10-28-2.bbk');
});

test('the backup password needs 8 characters, typed twice', function (array $data) {
    $this->seed(DemoTaxpayerSeeder::class);

    $this->post('/data/backup', $data)->assertSessionHasErrors('password');
    expect(File::exists($this->folder))->toBeFalse();
})->with([
    'too short' => [['password' => 'short', 'password_confirmation' => 'short']],
    'mismatch' => [['password' => 'correct horse', 'password_confirmation' => 'correct horsf']],
]);

test('restoring replaces the records and documents with the backup’s', function () {
    seedWithScan();
    $path = backUp();
    $scan = Certificate::query()->sole()->file_path;

    // Changes after the backup, which the restore takes back.
    Transaction::query()->where('counterparty', 'Studio Uno')->delete();
    Transaction::factory()->on('2026-10-27')->create(['counterparty' => 'Added later']);
    Filing::query()->delete();
    Storage::disk('local')->delete($scan);

    chooseBackup($path);
    $this->get('/data/backup')->assertInertia(fn (Assert $page) => $page
        ->where('pending.file', 'buwisbuddy-2026-10-28.bbk')
        ->where('pending.made_at', '2026-10-28T13:12:00+08:00')
        ->where('pending.summary.records', 18));

    $this->post('/data/restore/confirm', ['password' => 'correct horse'])->assertRedirect('/home');

    expect(Transaction::query()->count())->toBe(18)
        ->and(Transaction::query()->where('counterparty', 'Added later')->exists())->toBeFalse()
        ->and(Filing::query()->count())->toBe(2)
        ->and(Storage::disk('local')->get($scan))->toBe('%PDF-1.4 acme')
        ->and(Storage::disk('local')->exists('restore/pending.bbk'))->toBeFalse();
});

test('the wrong password restores nothing', function () {
    $this->seed(DemoTaxpayerSeeder::class);
    $path = backUp();
    Transaction::factory()->on('2026-10-27')->create(['counterparty' => 'Added later']);

    chooseBackup($path);
    $this->post('/data/restore/confirm', ['password' => 'wrong horse'])
        ->assertSessionHasErrors(['password' => 'That password doesn’t open this backup.']);

    expect(Transaction::query()->where('counterparty', 'Added later')->exists())->toBeTrue();
});

test('a new device restores before setup and lands on Home', function () {
    $this->seed(DemoTaxpayerSeeder::class);
    $path = backUp();
    copy($path, $copy = sys_get_temp_dir().'/'.uniqid().'.bbk');

    // A fresh device: nothing on it.
    foreach (['books_months', 'filings', 'transactions', 'certificates', 'tax_profiles', 'app_settings'] as $table) {
        DB::table($table)->delete();
    }

    $this->get('/')->assertInertia(fn (Assert $page) => $page->component('welcome'));
    $this->get('/data/restore')->assertOk()->assertInertia(fn (Assert $page) => $page->component('data/restore')->where('pending', null));

    chooseBackup($copy);
    $this->post('/data/restore/confirm', ['password' => 'correct horse'])->assertRedirect('/home');

    expect(TaxProfile::current()->full_name)->toBe('Juan Dela Cruz')
        ->and(Transaction::query()->count())->toBe(18);
    unlink($copy);
});

test('a file that isn’t a backup is turned away', function () {
    $this->seed(DemoTaxpayerSeeder::class);
    $fake = UploadedFile::fake()->createWithContent('photos.bbk', 'not a zip');

    $this->post('/data/restore', ['backup' => $fake])
        ->assertSessionHasErrors(['backup' => 'This isn’t a BuwisBuddy backup.']);
    $this->post('/data/restore', ['backup' => UploadedFile::fake()->create('notes.pdf', 10)])
        ->assertSessionHasErrors('backup');
});

test('a backup from a newer version of the app waits for an update', function () {
    $this->seed(DemoTaxpayerSeeder::class);
    $path = backUp();

    $zip = new ZipArchive;
    $zip->open($path);
    $manifest = json_decode($zip->getFromName('manifest.json'), true);
    $zip->addFromString('manifest.json', json_encode([...$manifest, 'schema' => '2099_01_01_000000_from_the_future']));
    $zip->close();

    $this->post('/data/restore', ['backup' => new UploadedFile($path, 'new.bbk', null, null, true)])
        ->assertSessionHasErrors(['backup' => 'This backup was made by a newer BuwisBuddy. Update the app, then restore it.']);
});

test('the backup folder can be changed, if BuwisBuddy can write there', function () {
    $this->seed(DemoTaxpayerSeeder::class);

    $this->put('/data', ['backup_folder' => $this->folder.'/elsewhere'])->assertSessionHasNoErrors();
    expect(Setting::get('backup_folder'))->toBe($this->folder.'/elsewhere')
        ->and(backUp())->toStartWith($this->folder.'/elsewhere/');

    $this->put('/data', ['backup_folder' => 'Documents/BuwisBuddy'])
        ->assertSessionHasErrors(['backup_folder' => 'Give the full path of the folder, like ~/Documents/BuwisBuddy.']);
});

test('Home asks for a backup when there is none, or the last is a month old', function () {
    $this->seed(DemoTaxpayerSeeder::class);

    $this->get('/home')->assertInertia(fn (Assert $page) => $page->where('backupReminder', ['days_since' => null]));

    backUp();
    $this->get('/home')->assertInertia(fn (Assert $page) => $page->where('backupReminder', null));

    $this->travelTo(CarbonImmutable::parse('2026-11-28 09:00'));
    $this->get('/home')->assertInertia(fn (Assert $page) => $page->where('backupReminder', ['days_since' => 30]));

    $this->put('/data', ['backup_reminder' => false]);
    $this->get('/home')->assertInertia(fn (Assert $page) => $page->where('backupReminder', null));
});

test('Your data shows the last backup and the folder', function () {
    $this->seed(DemoTaxpayerSeeder::class);
    backUp();
    $this->travelTo(CarbonImmutable::parse('2026-11-01 10:00'));

    $this->get('/data')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('data/show')
        ->where('folder', DataController::display($this->folder))
        ->where('lastBackup.days_since', 3)
        ->where('remindMonthly', true)
        ->where('rulePack', ['version' => '2026.11', 'reviewed' => false]));
});
