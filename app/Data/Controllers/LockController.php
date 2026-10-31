<?php

namespace App\Data\Controllers;

use App\Data\Requests\SetPinRequest;
use App\Data\Services\AppLock;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The lock screen, and the app lock under Your data.
 */
class LockController extends Controller
{
    public function __construct(private AppLock $lock) {}

    public function show(Request $request): Response|RedirectResponse
    {
        if (! $this->lock->isLocked($request)) {
            return redirect()->route('home');
        }

        return Inertia::render('lock', [
            'lockAfter' => $this->lock->lockAfter(),
        ]);
    }

    public function unlock(Request $request): RedirectResponse
    {
        $request->validate(['pin' => ['required', 'string']]);
        $key = 'unlock:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, config('lock.max_attempts'))) {
            throw ValidationException::withMessages([
                'pin' => 'Too many tries. Wait '.RateLimiter::availableIn($key).' seconds, then try again.',
            ]);
        }

        if (! $this->lock->check((string) $request->input('pin'))) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['pin' => 'That PIN isn’t right.']);
        }

        RateLimiter::clear($key);
        $this->lock->unlock($request);

        return redirect()->intended(route('home'));
    }

    /**
     * Locks this session now: from Your data, or when the browser has been
     * left alone for the lock time.
     */
    public function lockNow(Request $request): RedirectResponse
    {
        $this->lock->lock($request);

        return redirect()->route('lock');
    }

    public function update(SetPinRequest $request): RedirectResponse
    {
        if ($request->filled('pin')) {
            $this->lock->setPin((string) $request->validated('pin'));
            // Setting the PIN here is proof enough; don't lock the person out right away.
            $this->lock->unlock($request);
        }

        $this->lock->setLockAfter((int) $request->validated('lock_after'));

        return back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['current_pin' => ['required', 'string']]);

        if (! $this->lock->check((string) $request->input('current_pin'))) {
            throw ValidationException::withMessages(['current_pin' => 'That isn’t your current PIN.']);
        }

        $this->lock->disable();

        return Inertia::flash('toast', 'App lock is off.')->back();
    }
}
