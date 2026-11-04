<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Native\Desktop\Facades\Shell;

/**
 * In the desktop app, opens a link outside the app in the taxpayer's own
 * browser or email app: the app's window keeps to its own pages. Only the
 * links the app makes: a report on the issue tracker, an email draft.
 */
class ExternalLinkController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless((bool) config('nativephp-internal.running'), 404);

        $url = (string) $request->validate(['url' => ['required', 'string', 'max:8000']])['url'];
        $issues = rtrim((string) config('app.issues_url'), '/').'/';

        if (! str_starts_with($url, 'mailto:') && ! str_starts_with($url, $issues)) {
            throw ValidationException::withMessages(['url' => 'The app only opens its own issue tracker and email drafts.']);
        }

        Shell::openExternal($url);

        return back();
    }
}
