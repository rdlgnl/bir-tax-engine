<?php

namespace App\Assistant\Controllers;

use App\Assistant\Requests\UpdateAssistantSettingsRequest;
use App\Assistant\Services\AssistantSettings;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class AssistantSettingsController extends Controller
{
    public function update(UpdateAssistantSettingsRequest $request): RedirectResponse
    {
        AssistantSettings::current()->save(
            [
                'enabled' => $request->boolean('enabled'),
                'provider' => (string) $request->validated('provider'),
                'model' => (string) $request->validated('model'),
                'base_url' => $request->validated('base_url'),
            ],
            trim((string) $request->validated('key')),
            $request->boolean('remove_key'),
        );

        return Inertia::flash('toast', 'Assistant settings saved.')->back();
    }
}
