<?php

namespace App\Letters\Controllers;

use App\Assistant\Exceptions\AssistantUnavailable;
use App\Assistant\Services\LetterReader;
use App\Http\Controllers\Controller;
use App\Letters\Requests\ReadLetterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * The assistant reads a letter the taxpayer is adding, when they ask, and
 * fills in the form for them to check. Nothing is saved here.
 */
class LetterReadingController extends Controller
{
    public function __invoke(ReadLetterRequest $request, LetterReader $reader): RedirectResponse
    {
        set_time_limit(300);
        $file = $request->file('file');

        if (! $file instanceof UploadedFile) {
            throw ValidationException::withMessages(['file' => 'Add a photo or PDF of the letter first.']);
        }

        try {
            $reading = $reader->read($file);
        } catch (AssistantUnavailable $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }

        return back()->with('letter_reading', $reading);
    }
}
