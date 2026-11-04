<?php

use App\Desktop\DeviceKey;
use App\Profiles\Models\TaxProfile;
use App\Providers\AppServiceProvider;
use App\Providers\NativeAppServiceProvider;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Native\Desktop\Facades\Shell;
use Native\Desktop\Facades\Window;
use Native\Desktop\Windows\Window as NativeWindow;

beforeEach(function () {
    $this->folder = sys_get_temp_dir().'/buwisbuddy-desktop-'.Str::random(8);
});

afterEach(function () {
    foreach (glob($this->folder.'/*/*') ?: [] as $file) {
        unlink($file);
    }

    foreach (array_reverse(glob($this->folder.'/*') ?: []) as $dir) {
        @rmdir($dir);
    }

    @rmdir($this->folder);
});

test('each install makes its own key once, and keeps it to itself', function () {
    $path = $this->folder.'/app/device.key';
    $key = (new DeviceKey($path))->get('AES-256-CBC');

    expect($key)->toStartWith('base64:')
        ->and((new DeviceKey($path))->get('AES-256-CBC'))->toBe($key)
        ->and(fileperms($path) & 0777)->toBe(0600)
        ->and((new DeviceKey($this->folder.'/other/device.key'))->get('AES-256-CBC'))->not->toBe($key);

    $encrypter = new Encrypter(base64_decode(substr($key, 7)), 'AES-256-CBC');
    expect($encrypter->decryptString($encrypter->encryptString('sk-ant-secret')))->toBe('sk-ant-secret');

    // A key file that isn't a key is replaced.
    file_put_contents($path, 'not a key');
    expect((new DeviceKey($path))->get('AES-256-CBC'))->not->toBe('not a key')->toStartWith('base64:');
});

test('the desktop app encrypts with its own key, not the build’s', function () {
    $buildKey = config('app.key');
    app()->useStoragePath($this->folder);
    config(['nativephp-internal.running' => true]);

    (new AppServiceProvider(app()))->register();

    expect(config('app.key'))->not->toBe($buildKey)
        ->and(config('app.key'))->toBe(trim((string) file_get_contents($this->folder.'/app/device.key')));
});

test('outside the desktop app, the build’s key stays', function () {
    $key = config('app.key');
    (new AppServiceProvider(app()))->register();

    expect(config('app.key'))->toBe($key);
});

test('links out open in the computer’s browser or email app, only the app’s own', function () {
    TaxProfile::factory()->create();
    Shell::fake();
    $issue = 'https://github.com/rdlgnl/bir-tax-engine/issues/new?title=Rule%20IT-8PCT-PURE%20v2%20is%20wrong';

    // In a browser, links open as links: there's nothing to hand over.
    $this->post('/external', ['url' => $issue])->assertNotFound();
    $this->get('/home')->assertInertia(fn (Assert $page) => $page->where('desktop', false));

    config(['nativephp-internal.running' => true]);
    $this->get('/home')->assertInertia(fn (Assert $page) => $page->where('desktop', true));

    $this->post('/external', ['url' => $issue])->assertRedirect();
    $this->post('/external', ['url' => 'mailto:?subject=BIR%20Form%202307'])->assertRedirect();
    $this->post('/external', ['url' => 'https://example.com/phish'])->assertSessionHasErrors(['url' => 'The app only opens its own issue tracker and email drafts.']);
    $this->post('/external', ['url' => 'https://github.com/rdlgnl/bir-tax-engine/issues.evil.example'])->assertSessionHasErrors('url');

    Shell::assertOpenedExternal($issue);
    Shell::assertOpenedExternal('mailto:?subject=BIR%20Form%202307');
});

test('the window and the PHP settings the desktop app runs with', function () {
    // NativePHP's API, which the window calls as it's set up.
    Http::fake();
    Window::fake()->alwaysReturnWindows([$window = new NativeWindow('main')]);
    $provider = new NativeAppServiceProvider;
    $provider->boot();

    Window::assertOpenedCount(1);
    expect($window->toArray())->toMatchArray(['title' => 'BuwisBuddy', 'width' => 1280, 'minWidth' => 980, 'rememberState' => true, 'preventLeaveDomain' => true]);
    expect($provider->phpIni())->toMatchArray(['memory_limit' => '512M', 'max_execution_time' => '300', 'upload_max_filesize' => '12M']);
});

test('a build runs as production with debug off, and ships no one’s keys', function () {
    expect(config('nativephp.cleanup_env_keys'))->toContain('APP_ENV', 'APP_DEBUG', 'BACKUP_FOLDER', 'ANTHROPIC_*', 'OPENAI_*', 'GITHUB_*')
        ->and(config('nativephp.prebuild'))->toContain('npm run build')
        ->and(config('nativephp.app_id'))->toBe('io.github.rdlgnl.buwisbuddy')
        ->and(config('nativephp.updater.enabled'))->toBeFalse();
});
