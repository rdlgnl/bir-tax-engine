<?php

use App\Data\Models\Setting;
use App\Data\Services\AppLock;
use App\Profiles\Models\TaxProfile;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoTaxpayerSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-27 19:00'));
    $this->seed(DemoTaxpayerSeeder::class);
});

/** A PIN of 1234, locking after 5 minutes, and a fresh browser session. */
function lockedWithPin(): void
{
    app(AppLock::class)->setPin('1234');
    app(AppLock::class)->setLockAfter(5);
    test()->flushSession();
}

test('after setup the first run offers the lock, once', function () {
    TaxProfile::current()->update(['setup_completed_at' => null]);

    $this->post('/setup/complete')->assertRedirect('/first-run');
    $this->get('/first-run')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('first-run')
        ->where('lockOptions', [1, 5, 15])
        ->where('defaultLockAfter', 5));

    $this->post('/first-run', ['pin' => '4321', 'pin_confirmation' => '4321', 'lock_after' => 15, 'reminders' => true])
        ->assertRedirect('/home');

    expect(Setting::get('pin_hash'))->not->toBe('4321')
        ->and(app(AppLock::class)->check('4321'))->toBeTrue()
        ->and(Setting::get('lock_after'))->toBe(15)
        ->and(Setting::get('reminders'))->toBeTrue()
        ->and(Setting::get('first_run_done'))->toBeTrue();

    // Setting the PIN unlocks this session; finishing setup again goes straight Home.
    $this->get('/home')->assertOk();
    $this->post('/setup/complete')->assertRedirect('/home');
});

test('skipping the first run sets no PIN', function () {
    $this->post('/first-run/skip')->assertRedirect('/home');

    expect(app(AppLock::class)->enabled())->toBeFalse()
        ->and(Setting::get('first_run_done'))->toBeTrue();
    $this->get('/home')->assertOk();
});

test('the PIN is 4 to 6 digits, typed twice', function (array $data, string $message) {
    $this->post('/first-run', ['lock_after' => 5, ...$data])->assertSessionHasErrors(['pin' => $message]);
})->with([
    'too short' => [['pin' => '123', 'pin_confirmation' => '123'], 'Use 4 to 6 digits.'],
    'letters' => [['pin' => '12ab', 'pin_confirmation' => '12ab'], 'Use 4 to 6 digits.'],
    'mismatch' => [['pin' => '1234', 'pin_confirmation' => '1235'], 'The two PINs don’t match.'],
]);

test('with a PIN, every page waits behind the lock screen until unlocked', function () {
    lockedWithPin();

    $this->get('/file/2026/3/1701Q/pdf')->assertRedirect('/lock');
    // Unlocking returns to the last page asked for.
    $this->get('/transactions')->assertRedirect('/lock');
    $this->get('/lock')->assertOk()->assertInertia(fn (Assert $page) => $page->component('lock')->where('lockAfter', 5));

    $this->post('/lock', ['pin' => '0000'])->assertSessionHasErrors(['pin' => 'That PIN isn’t right.']);
    $this->post('/lock', ['pin' => '1234'])->assertRedirect('/transactions');

    $this->get('/home')->assertOk();
    $this->get('/lock')->assertRedirect('/home');
});

test('it locks again after the time away', function () {
    lockedWithPin();
    $this->post('/lock', ['pin' => '1234']);

    $this->travel(4)->minutes();
    $this->get('/home')->assertOk();

    $this->travel(6)->minutes();
    $this->get('/home')->assertRedirect('/lock');
});

test('too many wrong PINs make you wait', function () {
    lockedWithPin();

    foreach (range(1, 5) as $try) {
        $this->post('/lock', ['pin' => '0000']);
    }

    $this->post('/lock', ['pin' => '1234'])->assertSessionHasErrors('pin');
    expect(session('errors')->first('pin'))->toStartWith('Too many tries.');

    $this->travel(61)->seconds();
    $this->post('/lock', ['pin' => '1234'])->assertSessionHasNoErrors();
});

test('while locked, the lock can’t be changed or turned off', function () {
    lockedWithPin();

    $this->put('/data/lock', ['current_pin' => '1234', 'lock_after' => 1])->assertRedirect('/lock');
    $this->delete('/data/lock', ['current_pin' => '1234'])->assertRedirect('/lock');

    expect(app(AppLock::class)->enabled())->toBeTrue()
        ->and(app(AppLock::class)->lockAfter())->toBe(5);
});

test('changing or turning off the lock asks for the current PIN', function () {
    lockedWithPin();
    $this->post('/lock', ['pin' => '1234']);

    $this->put('/data/lock', ['current_pin' => '9999', 'pin' => '5678', 'pin_confirmation' => '5678', 'lock_after' => 1])
        ->assertSessionHasErrors(['current_pin' => 'That isn’t your current PIN.']);
    $this->put('/data/lock', ['current_pin' => '1234', 'pin' => '5678', 'pin_confirmation' => '5678', 'lock_after' => 1])
        ->assertSessionHasNoErrors();

    expect(app(AppLock::class)->check('5678'))->toBeTrue()
        ->and(app(AppLock::class)->lockAfter())->toBe(1);

    $this->delete('/data/lock', ['current_pin' => '1234'])->assertSessionHasErrors('current_pin');
    $this->delete('/data/lock', ['current_pin' => '5678'])->assertSessionHasNoErrors();
    expect(app(AppLock::class)->enabled())->toBeFalse();
});

test('lock now locks this session', function () {
    lockedWithPin();
    $this->post('/lock', ['pin' => '1234']);

    $this->post('/lock/now')->assertRedirect('/lock');
    $this->get('/home')->assertRedirect('/lock');
});

test('the browser learns the lock time and whether to remind', function () {
    $this->get('/home')->assertInertia(fn (Assert $page) => $page->where('appLock', null)->where('reminders', false));

    app(AppLock::class)->setPin('1234');
    $this->post('/lock', ['pin' => '1234']);
    $this->put('/data', ['reminders' => true]);

    $this->get('/home')->assertInertia(fn (Assert $page) => $page
        ->where('appLock', ['lock_after' => 5])
        ->where('reminders', true));
});
