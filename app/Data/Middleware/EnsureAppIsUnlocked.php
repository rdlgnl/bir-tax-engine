<?php

namespace App\Data\Middleware;

use App\Data\Services\AppLock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * With a PIN set, every page waits behind the lock screen until this
 * session unlocks, and again after the chosen time away.
 */
class EnsureAppIsUnlocked
{
    public function __construct(private AppLock $lock) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('lock', 'lock.unlock')) {
            return $next($request);
        }

        if ($this->lock->isLocked($request)) {
            if ($request->isMethod('GET') && ! $request->header('X-Inertia-Partial-Data')) {
                $request->session()->put('url.intended', $request->fullUrl());
            }

            return redirect()->route('lock');
        }

        $this->lock->touch($request);

        return $next($request);
    }
}
