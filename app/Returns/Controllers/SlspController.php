<?php

namespace App\Returns\Controllers;

use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxProfile;
use App\Returns\Services\RecordsExport;
use App\Returns\Services\Slsp;
use App\Tax\Services\FilingCalendar;
use App\Tax\Services\VatCalculator;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * The quarter's Summary List of Sales and Purchases, filed with the 2550Q.
 */
class SlspController extends Controller
{
    public function __construct(private Slsp $slsp) {}

    public function show(int $year, int $quarter, VatCalculator $vat, FilingCalendar $calendar): Response
    {
        $profile = $this->ensure($year, $quarter);
        $figures = $vat->forYear($year)[$quarter];

        return Inertia::render('returns/slsp', [
            'year' => $year,
            'quarter' => $quarter,
            ...$this->slsp->lists($year, $quarter),
            'totals' => [
                'vatable' => $figures['vatable_sales'],
                'output_vat' => $figures['output_vat'],
                'input_vat' => $figures['input_vat'],
                'excluded' => $figures['excluded_count'],
            ],
            'dueDate' => $calendar->vatDue($year, $quarter)->toDateString(),
            'files' => [
                'sales' => $this->slsp->fileName($profile, 'sales', $year, $quarter),
                'purchases' => $this->slsp->fileName($profile, 'purchases', $year, $quarter),
            ],
        ]);
    }

    public function export(int $year, int $quarter, string $kind, RecordsExport $export): HttpResponse
    {
        $profile = $this->ensure($year, $quarter);

        if ($kind === 'csv') {
            $lists = $this->slsp->lists($year, $quarter);
            $rows = [
                ...array_map(fn (array $r) => ['Sale', $r['name'], (string) $r['tin'], $r['invoices'], $r['vatable'], $r['zero_rated'], $r['exempt'], $r['output_vat']], $lists['sales']),
                ...array_map(fn (array $r) => ['Purchase', $r['name'], (string) $r['tin'], $r['invoices'], $r['amount'], 0, 0, $r['input_vat']], $lists['purchases']),
            ];

            return response($export->csvFile(['List', 'Customer / supplier', 'TIN', 'Invoices', 'Vatable amount', 'Zero-rated', 'Exempt', 'VAT'], $rows), headers: [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => "attachment; filename=\"slsp-{$year}-Q{$quarter}.csv\"",
            ]);
        }

        $name = $this->slsp->fileName($profile, $kind, $year, $quarter);
        abort_if($name === null, 404);

        return response($this->slsp->dat($profile, $kind, $year, $quarter), headers: [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$name}\"",
        ]);
    }

    /**
     * Only a VAT-registered taxpayer files an SLSP, for a quarter that has started.
     */
    private function ensure(int $year, int $quarter): TaxProfile
    {
        $profile = TaxProfile::current()->forYear($year);

        abort_unless(
            $profile->vat_registered && $year >= 2018 && CarbonImmutable::create($year, $quarter * 3 - 2)->lessThanOrEqualTo(CarbonImmutable::now()),
            404,
        );

        return $profile;
    }
}
