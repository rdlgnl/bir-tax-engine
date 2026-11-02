<?php

namespace App\Returns\Controllers;

use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxProfile;
use App\Returns\Services\Sawt;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * The quarter's withholding summary (SAWT): the 2307 credits the income
 * tax return claims, and the data file that goes with it.
 */
class SawtController extends Controller
{
    public function __construct(private Sawt $sawt) {}

    public function show(int $year, int $quarter): Response
    {
        $this->ensureQuarter($year, $quarter);
        $profile = TaxProfile::current();
        $rows = $this->sawt->rows($year, $quarter);

        return Inertia::render('returns/sawt', [
            'year' => $year,
            'quarter' => $quarter,
            'form' => $this->sawt->form($profile, $year, $quarter),
            'rows' => $rows,
            'totals' => [
                'clients' => count($rows),
                'income' => round(array_sum(array_column($rows, 'income')), 2),
                'withheld' => round(array_sum(array_column($rows, 'withheld')), 2),
            ],
            'fileName' => $this->sawt->fileName($profile, $year, $quarter),
        ]);
    }

    public function export(int $year, int $quarter): HttpResponse
    {
        $this->ensureQuarter($year, $quarter);
        $profile = TaxProfile::current();
        $name = $this->sawt->fileName($profile, $year, $quarter);

        // The file is named by the taxpayer's TIN; without it there is nothing the BIR can take.
        abort_if($name === null, 404);

        return response($this->sawt->dat($profile, $year, $quarter), headers: [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$name}\"",
        ]);
    }

    private function ensureQuarter(int $year, int $quarter): void
    {
        abort_if($year < 2018 || CarbonImmutable::create($year, $quarter * 3 - 2)->isAfter(CarbonImmutable::now()), 404);
    }
}
