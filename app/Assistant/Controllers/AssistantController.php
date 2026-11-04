<?php

namespace App\Assistant\Controllers;

use App\Assistant\Exceptions\AssistantUnavailable;
use App\Assistant\Requests\AskRequest;
use App\Assistant\Services\Assistant;
use App\Assistant\Services\AssistantSettings;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Ask about your taxes: answers from the tax engine's figures, through the
 * taxpayer's own AI provider, when they've turned it on.
 */
class AssistantController extends Controller
{
    public function show(Assistant $assistant): Response
    {
        $settings = AssistantSettings::current();

        return Inertia::render('ask', [
            'ready' => $settings->ready(),
            'enabled' => $settings->enabled,
            'provider' => $settings->label(),
            'turns' => $assistant->turns(),
            'suggestions' => ["What's a 2307?", 'Should I switch tax method next year?', "What's due next?"],
        ]);
    }

    public function ask(AskRequest $request, Assistant $assistant): RedirectResponse
    {
        // An answer that looks figures up can take a while.
        set_time_limit(300);

        try {
            $assistant->ask((string) $request->validated('question'));
        } catch (AssistantUnavailable $e) {
            throw ValidationException::withMessages(['question' => $e->getMessage()]);
        }

        return back();
    }

    public function feedback(Request $request, Assistant $assistant): RedirectResponse
    {
        $data = $request->validate([
            'turn' => ['required', 'integer', 'min:0'],
            'feedback' => ['required', Rule::in(['helpful', 'wrong'])],
        ]);
        $assistant->feedback((int) $data['turn'], $data['feedback']);

        return back();
    }

    public function clear(Assistant $assistant): RedirectResponse
    {
        $assistant->clear();

        return back();
    }
}
