<?php

namespace App\Data\Services;

use App\Data\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * The app lock: a PIN, and how long the app stays open while nobody uses
 * it. Being unlocked belongs to the browser session.
 */
class AppLock
{
    public function enabled(): bool
    {
        return Setting::get('pin_hash') !== null;
    }

    public function lockAfter(): int
    {
        return (int) Setting::get('lock_after', config('lock.default_after'));
    }

    public function setPin(string $pin): void
    {
        Setting::put('pin_hash', Hash::make($pin));
    }

    public function setLockAfter(int $minutes): void
    {
        Setting::put('lock_after', $minutes);
    }

    public function disable(): void
    {
        Setting::query()->whereKey('pin_hash')->delete();
    }

    public function check(string $pin): bool
    {
        $hash = Setting::get('pin_hash');

        return is_string($hash) && Hash::check($pin, $hash);
    }

    /**
     * Locked when there's a PIN and this session hasn't unlocked, or has
     * been away longer than the lock time.
     */
    public function isLocked(Request $request): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        $seen = $request->session()->get('lock.seen_at');

        return ! is_int($seen) || now()->getTimestamp() - $seen > $this->lockAfter() * 60;
    }

    public function touch(Request $request): void
    {
        $request->session()->put('lock.seen_at', now()->getTimestamp());
    }

    public function unlock(Request $request): void
    {
        $request->session()->regenerate();
        $this->touch($request);
    }

    public function lock(Request $request): void
    {
        $request->session()->forget('lock.seen_at');
    }
}
