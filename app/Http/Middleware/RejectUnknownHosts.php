<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Answers only requests addressed to this computer. The app has no login,
 * so a web page that points its own domain at 127.0.0.1 (DNS rebinding)
 * could otherwise read the taxpayer's records through the browser.
 *
 * Laravel's TrustHosts can't do this: it stands down in the local
 * environment, which is where this app always runs.
 */
class RejectUnknownHosts
{
    /** This computer, by name and address. */
    private const LOCAL = ['localhost', '127.0.0.1', '[::1]'];

    public function handle(Request $request, Closure $next): Response
    {
        // A bare response: the app's error pages carry the taxpayer's details.
        if (! in_array(strtolower($request->getHost()), $this->allowed(), true)) {
            return response('This app only answers on this computer.', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        return $next($request);
    }

    /**
     * @return list<string>
     */
    private function allowed(): array
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        return array_values(array_unique([...self::LOCAL, ...(is_string($appHost) ? [strtolower($appHost)] : [])]));
    }
}
