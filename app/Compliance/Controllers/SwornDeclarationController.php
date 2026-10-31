<?php

namespace App\Compliance\Controllers;

use App\Compliance\Services\SwornDeclarations;
use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxProfile;
use App\Tax\Services\TaxCalculationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Sworn declarations for the clients who withhold: one each, to sign
 * before a notary and give them by January 15.
 */
class SwornDeclarationController extends Controller
{
    public function __construct(
        private SwornDeclarations $declarations,
        private TaxCalculationService $engine,
    ) {}

    public function show(?int $year = null): Response
    {
        $today = CarbonImmutable::now();
        $year ??= $this->declarations->defaultYear($today);
        $this->ensureYear($year, $today);
        $profile = TaxProfile::current();

        return Inertia::render('compliance/sworn', [
            'declaration' => $this->declarations->declaration($profile, $year, $this->engine->forYear($profile, $year, $today)['gross']),
            'clients' => $this->declarations->clients($year),
            'paidInFull' => $this->declarations->paidInFull($year),
        ]);
    }

    /**
     * The declarations as one PDF, a page per client, or one client's alone.
     */
    public function pdf(int $year, ?string $client = null): HttpResponse
    {
        $today = CarbonImmutable::now();
        $this->ensureYear($year, $today);
        $profile = TaxProfile::current();
        $clients = array_values(array_filter(
            $this->declarations->clients($year),
            fn (array $c) => $client === null || $c['key'] === $client,
        ));

        abort_if($clients === [], 404);

        return Pdf::loadView('compliance.sworn-declaration', [
            'declaration' => $this->declarations->declaration($profile, $year, $this->engine->forYear($profile, $year, $today)['gross']),
            'clients' => $clients,
        ])->setPaper([0, 0, 612, 936])->download($client === null
            ? "sworn-declarations-{$year}.pdf"
            : "sworn-declaration-{$year}-{$client}.pdf");
    }

    private function ensureYear(int $year, CarbonImmutable $today): void
    {
        abort_if($year < 2018 || $year > $today->year, 404);
    }
}
