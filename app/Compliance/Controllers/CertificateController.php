<?php

namespace App\Compliance\Controllers;

use App\Compliance\Models\Certificate;
use App\Compliance\Requests\MatchCertificateRequest;
use App\Compliance\Requests\ReadCertificateRequest;
use App\Compliance\Requests\StoreCertificateRequest;
use App\Compliance\Services\CertificateLinks;
use App\Compliance\Services\CertificateMatcher;
use App\Compliance\Services\CertificateReader;
use App\Http\Controllers\Controller;
use App\Notices\Services\Notices;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CertificateController extends Controller
{
    public function create(Request $request): Response
    {
        $today = CarbonImmutable::now();
        $prefill = $request->integer('transaction')
            ? Transaction::query()->find($request->integer('transaction'))
            : null;

        $quarters = collect([$today->year, $today->year - 1])
            ->flatMap(fn (int $year) => collect([4, 3, 2, 1])->map(fn (int $q) => ['year' => $year, 'quarter' => $q]))
            ->filter(fn (array $q) => CarbonImmutable::create($q['year'], $q['quarter'] * 3 - 2)->lessThanOrEqualTo($today))
            ->values();

        return Inertia::render('certificates/create', [
            'clients' => Transaction::query()
                ->where('tax_withheld', '>', 0)
                ->where('occurred_on', '>=', $today->subYear()->startOfYear()->toDateString())
                ->orderBy('counterparty')
                ->pluck('counterparty')
                ->unique()
                ->values(),
            'quarters' => $quarters,
            'prefill' => $prefill ? [
                'payor_name' => $prefill->counterparty,
                'year' => $prefill->occurred_on->year,
                'quarter' => $prefill->quarter(),
                'amount_paid' => (float) Transaction::query()->forQuarter($prefill->occurred_on->year, $prefill->quarter())
                    ->where('counterparty', $prefill->counterparty)->where('tax_withheld', '>', 0)->sum('amount'),
                'tax_withheld' => (float) Transaction::query()->forQuarter($prefill->occurred_on->year, $prefill->quarter())
                    ->where('counterparty', $prefill->counterparty)->sum('tax_withheld'),
            ] : null,
        ]);
    }

    public function match(MatchCertificateRequest $request, CertificateMatcher $matcher): JsonResponse
    {
        $match = $matcher->match(
            $request->validated('payor_name'),
            $request->integer('year'),
            $request->integer('quarter'),
            (float) $request->validated('tax_withheld', 0),
        );

        return response()->json([
            'status' => $match['status'],
            'withheld' => $match['withheld'],
            'payments' => $match['payments']->map(fn (Transaction $t) => [
                'id' => $t->id,
                'counterparty' => $t->counterparty,
                'occurred_on' => $t->occurred_on->toDateString(),
                'amount' => (float) $t->amount,
                'tax_withheld' => (float) $t->tax_withheld,
            ])->values(),
        ]);
    }

    /**
     * Reads the chosen file's details for the taxpayer to check (design
     * 3-02). The file is read where it was uploaded to and not kept; it's
     * stored only when the certificate is saved.
     */
    public function read(ReadCertificateRequest $request, CertificateReader $reader): JsonResponse
    {
        $file = $request->file('file');

        return response()->json($reader->read((string) $file?->getRealPath(), (string) $file?->getMimeType()));
    }

    public function store(StoreCertificateRequest $request, CertificateMatcher $matcher, Notices $notices): RedirectResponse
    {
        $file = $request->file('file');
        $path = $file?->store('certificates', 'local');

        try {
            [$certificate, $match] = DB::transaction(function () use ($request, $matcher, $file, $path) {
                $certificate = new Certificate($request->safe()->except('file'));
                $certificate->file_path = $path ?: null;
                $certificate->original_filename = $file?->getClientOriginalName();
                $certificate->save();

                $match = $matcher->match(
                    $certificate->payor_name,
                    $certificate->year,
                    $certificate->quarter,
                    (float) $certificate->tax_withheld,
                );

                // Only a certificate that accounts for every peso confirms the credit.
                if ($match['status'] === 'exact') {
                    Transaction::query()->whereKey($match['payments']->pluck('id')->all())->update([
                        'certificate_id' => $certificate->id,
                        'certificate_received_on' => now()->toDateString(),
                    ]);
                }

                return [$certificate, $match];
            });
        } catch (Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }

            throw $e;
        }

        $peso = fn (float $amount) => '₱'.Number::format($amount, maxPrecision: 2);

        $notices->record('certificate', match ($match['status']) {
            'exact' => "2307 from {$certificate->payor_name} matched",
            'partial' => "2307 from {$certificate->payor_name} doesn’t match your payments",
            default => "2307 from {$certificate->payor_name} added",
        }, "Q{$certificate->quarter} {$certificate->year} · {$peso((float) $certificate->tax_withheld)} withheld",
            route('compliance.show', absolute: false));

        Inertia::flash('toast', match ($match['status']) {
            'exact' => "2307 from {$certificate->payor_name} saved. The {$peso((float) $certificate->tax_withheld)} credit is confirmed.",
            'partial' => "2307 saved, but your payments show {$peso($match['withheld'])} withheld. Correct the payment, check with {$certificate->payor_name}, or link it anyway from Compliance.",
            default => '2307 saved. No matching payment yet: record the payment and we’ll match it.',
        });

        return redirect()->route('compliance.show', $certificate->year === now()->year ? $certificate->quarter : null);
    }

    /**
     * Links a 2307 that doesn't match to the client's payments in its
     * quarter, as the taxpayer decided. The credit is what the payments show.
     */
    public function link(Certificate $certificate, CertificateLinks $links): RedirectResponse
    {
        $count = $links->linkByHand($certificate);

        return Inertia::flash('toast', $count > 0
            ? "2307 from {$certificate->payor_name} linked to {$count} ".($count === 1 ? 'payment' : 'payments').'.'
            : "No payment from {$certificate->payor_name} in Q{$certificate->quarter} to link it to.")->back();
    }

    public function file(Certificate $certificate): StreamedResponse
    {
        abort_unless($certificate->file_path && Storage::disk('local')->exists($certificate->file_path), 404);

        return Storage::disk('local')->response($certificate->file_path, $certificate->original_filename);
    }
}
