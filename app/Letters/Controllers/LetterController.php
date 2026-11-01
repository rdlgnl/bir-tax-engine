<?php

namespace App\Letters\Controllers;

use App\Http\Controllers\Controller;
use App\Letters\Models\Letter;
use App\Letters\Requests\StoreLetterRequest;
use App\Letters\Services\LetterCheck;
use App\Profiles\Models\TaxProfile;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Letters from the BIR (design 9-07): what the letter is, what the
 * taxpayer's records say about it, and what to send or bring. Everything
 * stays on this computer.
 */
class LetterController extends Controller
{
    public function __construct(private LetterCheck $check) {}

    public function index(): Response
    {
        return Inertia::render('letters/index', [
            'letters' => Letter::query()->latest('id')->get()->map(fn (Letter $l) => $this->resource($l))->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('letters/create', [
            'kinds' => collect((array) config('letters.kinds'))->map(fn (array $k, string $key) => ['value' => $key, 'label' => $k['label']])->values(),
            'forms' => config('letters.forms'),
        ]);
    }

    public function store(StoreLetterRequest $request): RedirectResponse
    {
        $letter = new Letter($request->safe()->except('file'));

        if ($file = $request->file('file')) {
            $letter->file_path = $file->store('certificates/letters', 'local') ?: null;
            $letter->original_filename = $file->getClientOriginalName();
        }

        $letter->save();
        Inertia::flash('toast', 'Letter saved to your documents.');

        return redirect()->route('letters.show', $letter);
    }

    public function show(Letter $letter): Response
    {
        return Inertia::render('letters/show', [
            'letter' => $this->resource($letter),
            'check' => $this->check->for($letter, TaxProfile::current()),
        ]);
    }

    public function resolve(Letter $letter): RedirectResponse
    {
        $letter->forceFill(['resolved_on' => CarbonImmutable::now()->toDateString()])->save();

        return Inertia::flash('toast', 'Letter marked as dealt with.')->back();
    }

    public function destroy(Letter $letter): RedirectResponse
    {
        if ($letter->file_path) {
            Storage::disk('local')->delete($letter->file_path);
        }

        $letter->delete();
        Inertia::flash('toast', 'Letter deleted.');

        return redirect()->route('letters.index');
    }

    public function file(Letter $letter): StreamedResponse
    {
        abort_unless($letter->file_path && Storage::disk('local')->exists($letter->file_path), 404);

        return Storage::disk('local')->response($letter->file_path, $letter->original_filename);
    }

    /**
     * @return array<string, mixed>
     */
    private function resource(Letter $letter): array
    {
        return [
            'id' => $letter->id,
            'kind' => $letter->kind,
            'kind_label' => $letter->kindLabel(),
            'needs_help' => $letter->needsHelp(),
            'dated_on' => $letter->dated_on?->toDateString(),
            'office' => $letter->office,
            'form' => $letter->form,
            'period' => $this->check->period($letter),
            'reply_by' => $letter->reply_by?->toDateString(),
            'notes' => $letter->notes,
            'has_file' => $letter->file_path !== null,
            'original_filename' => $letter->original_filename,
            'resolved_on' => $letter->resolved_on?->toDateString(),
        ];
    }
}
