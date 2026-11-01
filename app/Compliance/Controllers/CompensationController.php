<?php

namespace App\Compliance\Controllers;

use App\Compliance\Models\CompensationCertificate;
use App\Compliance\Requests\StoreCompensationRequest;
use App\Http\Controllers\Controller;
use App\Profiles\Enums\IncomeSource;
use App\Profiles\Models\TaxProfile;
use App\Tax\Services\Compensation;
use App\Tax\Services\TaxCalculationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The 2316 from work (design 11-08): the year's salary and the tax the
 * employer withheld, compared with the estimate and used on the annual return.
 */
class CompensationController extends Controller
{
    public function show(TaxCalculationService $engine, Compensation $compensation, ?int $year = null): Response
    {
        // The 2316 comes by January 31 for the year before.
        $year ??= CarbonImmutable::now()->month <= 4 ? CarbonImmutable::now()->year - 1 : CarbonImmutable::now()->year;
        $profile = $this->ensure($year);
        $certificate = CompensationCertificate::query()->where('year', $year)->first();
        $estimate = $compensation->estimate($profile);
        $figures = fn (float $gross, float $nonTaxable, float $withheld) => [
            'taxable' => round($gross - $nonTaxable, 2),
            'withheld' => $withheld,
            'tax' => $engine->graduatedTax($gross - $nonTaxable, $year),
        ];

        return Inertia::render('compliance/compensation', [
            'year' => $year,
            'employer' => $profile->employer_name,
            'certificate' => $certificate === null ? null : [
                'employer_name' => $certificate->employer_name,
                'employer_tin' => $certificate->employer_tin,
                'gross_compensation' => (float) $certificate->gross_compensation,
                'non_taxable_compensation' => (float) $certificate->non_taxable_compensation,
                'tax_withheld' => (float) $certificate->tax_withheld,
                'original_filename' => $certificate->original_filename,
            ],
            'estimate' => $estimate === null ? null : $figures($estimate['gross'], 0.0, $engine->graduatedTax($estimate['gross'], $year)),
            'actual' => $certificate === null ? null : $figures(
                (float) $certificate->gross_compensation,
                (float) $certificate->non_taxable_compensation,
                (float) $certificate->tax_withheld,
            ),
        ]);
    }

    public function store(StoreCompensationRequest $request, int $year): RedirectResponse
    {
        $this->ensure($year);
        $certificate = CompensationCertificate::query()->firstOrNew(['year' => $year]);
        $certificate->fill([...$request->safe()->except('file'), 'non_taxable_compensation' => $request->validated('non_taxable_compensation') ?? 0]);

        if ($file = $request->file('file')) {
            $previous = $certificate->file_path;
            $certificate->file_path = $file->store('certificates/2316', 'local') ?: null;
            $certificate->original_filename = $file->getClientOriginalName();

            if ($previous) {
                Storage::disk('local')->delete($previous);
            }
        }

        $certificate->save();

        return Inertia::flash('toast', "Your {$year} 2316 is saved. The annual return uses its figures.")->back();
    }

    public function file(int $year): StreamedResponse
    {
        $this->ensure($year);
        $certificate = CompensationCertificate::query()->where('year', $year)->firstOrFail();
        abort_unless($certificate->file_path && Storage::disk('local')->exists($certificate->file_path), 404);

        return Storage::disk('local')->response($certificate->file_path, $certificate->original_filename);
    }

    private function ensure(int $year): TaxProfile
    {
        $profile = TaxProfile::current();
        abort_unless($profile?->income_source === IncomeSource::Both && $year >= 2018 && $year <= CarbonImmutable::now()->year, 404);

        return $profile;
    }
}
