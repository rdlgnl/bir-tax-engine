<?php

namespace App\Tax\Controllers;

use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxProfile;
use App\Tax\Services\RulePacks;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "What changed": the rule pack in use, what it brought, and the
 * taxpayer's numbers before and after it.
 */
class RuleController extends Controller
{
    public function __construct(private RulePacks $packs) {}

    public function show(): Response
    {
        $profile = TaxProfile::current();
        $today = CarbonImmutable::now();

        return Inertia::render('tax/rules', [
            'version' => $this->packs->version(),
            'reviewed' => (bool) config('tax.reviewed'),
            // Each kind of rule, reviewed and versioned on its own.
            'parts' => array_values(array_map(
                fn (array $part) => ['title' => (string) $part['title'], 'version' => (string) $part['version'], 'reviewed' => (bool) $part['reviewed']],
                (array) config('tax.parts'),
            )),
            'changes' => $this->packs->changes(),
            'pending' => $this->packs->pending(),
            'previousVersion' => $this->packs->seen()['version'] ?? null,
            'before' => $this->packs->pending() ? $this->packs->seen()['numbers'] ?? null : null,
            'after' => $this->packs->numbers($profile, $today),
        ]);
    }

    public function seen(): RedirectResponse
    {
        if ($this->packs->pending()) {
            $this->packs->acknowledge(TaxProfile::current(), CarbonImmutable::now());
        }

        return redirect()->route('home');
    }
}
