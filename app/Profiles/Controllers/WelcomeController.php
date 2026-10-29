<?php

namespace App\Profiles\Controllers;

use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxProfile;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    public function __invoke(): Response|RedirectResponse
    {
        if (TaxProfile::current()?->isComplete()) {
            return redirect()->route('home');
        }

        return Inertia::render('welcome', [
            'setupStarted' => TaxProfile::current() !== null,
        ]);
    }
}
