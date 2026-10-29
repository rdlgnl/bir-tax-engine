<?php

namespace App\Profiles\Middleware;

use App\Profiles\Models\TaxProfile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSetupIsComplete
{
    /**
     * Send people who haven't finished setup back to the welcome screen.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! TaxProfile::current()?->isComplete()) {
            return redirect()->route('welcome');
        }

        return $next($request);
    }
}
