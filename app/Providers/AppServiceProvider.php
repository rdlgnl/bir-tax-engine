<?php

namespace App\Providers;

use App\Desktop\DeviceKey;
use App\Tax\Services\TaxRules;
use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One rule lookup per request, so pinning a filed year's rules reaches every service.
        $this->app->scoped(TaxRules::class);

        // The desktop app encrypts with a key of its own, not the build's
        // APP_KEY. NativePHP has moved the storage path to the app's data
        // folder by now; nothing is encrypted yet.
        if (config('nativephp-internal.running')) {
            config(['app.key' => (new DeviceKey(storage_path('app/device.key')))->get((string) config('app.cipher'))]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Inertia props are read as plain objects, not { data: ... }.
        JsonResource::withoutWrapping();

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
