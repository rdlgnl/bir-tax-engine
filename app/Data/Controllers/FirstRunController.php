<?php

namespace App\Data\Controllers;

use App\Data\Models\Setting;
use App\Data\Requests\SetPinRequest;
use App\Data\Services\AppLock;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Two quick things" after setup: lock the app, and deadline reminders.
 * Shown once; both can be changed later under Your data.
 */
class FirstRunController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('first-run', [
            'lockOptions' => config('lock.after_options'),
            'defaultLockAfter' => config('lock.default_after'),
        ]);
    }

    public function store(SetPinRequest $request, AppLock $lock): RedirectResponse
    {
        $lock->setPin((string) $request->validated('pin'));
        $lock->setLockAfter((int) $request->validated('lock_after'));
        $lock->unlock($request);
        Setting::put('reminders', $request->boolean('reminders', true));

        return $this->done();
    }

    public function skip(): RedirectResponse
    {
        return $this->done();
    }

    private function done(): RedirectResponse
    {
        Setting::put('first_run_done', true);

        return redirect()->route('home');
    }
}
