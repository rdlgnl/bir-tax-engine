<?php

namespace App\Returns\Controllers;

use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxProfile;
use App\Returns\Requests\ExportPackageRequest;
use App\Returns\Services\RecordsExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Downloads made from the taxpayer's records. Nothing is uploaded anywhere;
 * the files go straight to the person's computer.
 */
class ReportController extends Controller
{
    public function __construct(private RecordsExport $export) {}

    public function summary(int $year): Response
    {
        $this->ensureYear($year);

        return $this->export->taxSummary(TaxProfile::current(), $year)->download("tax-summary-{$year}.pdf");
    }

    public function income(int $year): Response
    {
        $this->ensureYear($year);

        return $this->csv($this->export->incomeCsv($year), "income-and-withholding-{$year}.csv");
    }

    public function sawt(int $year, int $quarter): Response
    {
        $this->ensureYear($year);

        return $this->csv($this->export->sawtCsv($year, $quarter), "sawt-{$year}-Q{$quarter}.csv");
    }

    public function package(ExportPackageRequest $request, int $year): BinaryFileResponse
    {
        $this->ensureYear($year);

        $path = $this->export->package(TaxProfile::current(), $year, $request->validated('password'));

        return response()->download($path, "buwisbuddy-{$year}-for-accountant.zip", [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend();
    }

    private function csv(string $contents, string $name): Response
    {
        return response($contents, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$name}\"",
        ]);
    }

    private function ensureYear(int $year): void
    {
        abort_if($year > now()->year || $year < 2018, 404);
    }
}
