<?php

namespace App\Returns\Controllers;

use App\Compliance\Models\Certificate;
use App\Compliance\Services\TaxObligations;
use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\OfficialForms;
use App\Returns\Services\ReturnForm;
use App\Returns\Services\ReturnHistory;
use App\Tax\Enums\DeductionMethod;
use App\Tax\Enums\Regime;
use App\Tax\Enums\TaxpayerClassification;
use App\Tax\Services\Abatement;
use App\Tax\Services\FilingCalendar;
use App\Tax\Services\Penalties;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use ZipArchive;

/**
 * Preparing a return: the values to copy into eBIRForms, the filled form to
 * print, and marking it filed.
 *
 * @phpstan-import-type Snapshot from ReturnForm
 * @phpstan-import-type Layout from ReturnForm
 * @phpstan-import-type Offer from Abatement
 */
class FileReturnController extends Controller
{
    public function __construct(
        private ReturnForm $returns,
        private ReturnHistory $history,
        private FilingCalendar $calendar,
        private TaxCalculationService $engine,
    ) {}

    /**
     * "File a return" opens the most pressing one, or says nothing is due.
     */
    public function index(TaxCalculationService $engine, TaxObligations $obligations): Response|RedirectResponse
    {
        $profile = TaxProfile::current();
        $today = CarbonImmutable::now();
        $next = $obligations->nextPayment($profile, $engine->forYear($profile, $today->year, $today), $today);

        if ($next['quarter'] !== null) {
            return redirect()->route('returns.show', [$next['year'], $next['quarter']['quarter'], $next['quarter']['form']]);
        }

        $due = collect($this->history->entries($profile, $today))
            ->filter(fn (array $entry) => in_array($entry['status'], ['due', 'overdue'], true))
            ->sortBy('due_date')
            ->first();

        if ($due !== null) {
            return redirect()->route('returns.show', [$due['year'], $due['quarter'], $due['form']]);
        }

        return Inertia::render('returns/nothing-due', [
            'nextDeadline' => $next['next_deadline'],
            'daysUntilNext' => $next['days_until_next'],
        ]);
    }

    public function show(int $year, int $quarter, string $form, TaxObligations $obligations, FilingCalendar $calendar): Response|RedirectResponse
    {
        $profile = $this->ensureForm($year, $quarter, $form);
        $today = CarbonImmutable::now();

        if ($this->history->filingFor($year, $quarter, $form) !== null) {
            return redirect()->route('history', ['year' => $year, 'quarter' => $quarter, 'form' => $form]);
        }

        $snapshot = $this->returns->snapshot($profile, $year, $quarter, $form, $today);
        $transactions = Transaction::query()->forYear($year)->get();
        $periodEnd = CarbonImmutable::create($year, $quarter * 3)->endOfMonth();
        $layout = $this->returns->layout($snapshot);

        [$taxpayer, $forms, $alternative] = $this->quarter($profile, $year, $quarter);

        if ($quarter === 4 && $form === $snapshot['figures']['form']) {
            return $this->annual($snapshot, $layout, $transactions, $today, $periodEnd, $alternative);
        }

        return Inertia::render('returns/show', [
            'layout' => $layout,
            'dueDate' => match ($form) {
                '2551Q' => $calendar->percentageTaxDue($year, $quarter)->toDateString(),
                '2550Q' => $calendar->vatDue($year, $quarter)->toDateString(),
                default => $snapshot['figures']['due_date'],
            },
            'periodEnded' => $today->greaterThan($periodEnd),
            'periodEnd' => $periodEnd->toDateString(),
            'missingCertificates' => in_array($form, ['2551Q', '2550Q'], true) ? [] : $obligations->checklist(
                $profile, $year, $snapshot['figures'], $transactions, $today,
            )['missing_certificates'],
            'otherForms' => array_values(array_diff($forms, [$form])),
            'paperFiling' => $this->paperFiling($form),
            'abatement' => $this->abatement($profile, $snapshot),
        ]);
    }

    /**
     * RR 4-2026's abatement, for a late return a micro taxpayer can settle.
     *
     * @param  Snapshot  $snapshot
     * @return Offer|null
     */
    private function abatement(TaxProfile $profile, array $snapshot): ?array
    {
        if ($snapshot['penalties'] === null) {
            return null;
        }

        return app(Abatement::class)->offer(
            $snapshot['year'], CarbonImmutable::parse($snapshot['figures']['due_date']), $this->engine->forYear($profile, $snapshot['year'])['gross'],
        );
    }

    /**
     * The annual return: the year's figures in plain words, what is still to
     * pay, and what to do with the 2307s. Before December ends it is an
     * estimate from the records so far.
     *
     * @param  Snapshot  $snapshot
     * @param  Layout  $layout
     * @param  Collection<int, Transaction>  $transactions
     */
    private function annual(array $snapshot, array $layout, Collection $transactions, CarbonImmutable $today, CarbonImmutable $periodEnd, ?string $alternative): Response
    {
        $f = $snapshot['figures'];
        $eightPercent = $snapshot['regime'] === Regime::EightPercent->value;

        return Inertia::render('returns/annual', [
            'layout' => $layout,
            'dueDate' => $f['due_date'],
            'periodEnded' => $today->greaterThan($periodEnd),
            'periodEnd' => $periodEnd->toDateString(),
            'eightPercent' => $eightPercent,
            'figures' => [
                'gross' => $f['gross'],
                'less' => $eightPercent ? $f['exempt_amount'] : $f['deduction'],
                'income_tax' => $f['income_tax'],
                'prior_payments' => $f['prior_payments'],
                'withheld' => $f['withheld'],
                'payable' => $layout['payable'],
                'overpayment' => $layout['overpayment'],
            ],
            // Every 2307 not in yet, late or not: each one is a credit the return can't claim without it.
            'certificatesNotIn' => $transactions
                ->filter(fn (Transaction $t) => (float) $t->tax_withheld > 0 && $t->certificate_received_on === null)
                ->groupBy('counterparty')
                ->map(fn (Collection $payments, string $client) => ['counterparty' => $client, 'withheld' => round((float) $payments->sum('tax_withheld'), 2)])
                ->values()
                ->all(),
            'scans' => Certificate::query()->where('year', $snapshot['year'])->whereNotNull('file_path')->count(),
            'alternativeForm' => $alternative !== $snapshot['form'] ? $alternative : null,
            'paperFiling' => $this->paperFiling($snapshot['form']),
            'financialStatements' => $this->financialStatements($snapshot),
        ]);
    }

    /**
     * Whether a form can be filed on paper: the 1701-MS of micro and small
     * taxpayers. Everything else is e-filed, its filled form a worksheet.
     */
    private function paperFiling(string $form): bool
    {
        return in_array($form, (array) config('tax.filing.paper_forms'), true);
    }

    /**
     * What goes with the annual return: nothing on 8% or the 40% deduction;
     * with itemized deductions, financial statements, audited by an
     * independent CPA once gross sales exceed ₱3,000,000 (RMC 17-2019; NIRC
     * Sec. 232(A); RR 8-2018 for a year that left 8%).
     *
     * @param  Snapshot  $snapshot
     * @return 'none'|'unaudited'|'audited'
     */
    private function financialStatements(array $snapshot): string
    {
        return match (true) {
            $snapshot['regime'] !== Regime::Graduated->value || $snapshot['deduction_method'] !== DeductionMethod::Itemized->value => 'none',
            $snapshot['figures']['gross'] > (float) config('tax.financial_statements.audit_above') => 'audited',
            default => 'unaudited',
        };
    }

    /**
     * The year's 2307 scans in one ZIP, to upload through the BIR's eAFS
     * after filing the annual return.
     */
    public function eafs(int $year): BinaryFileResponse
    {
        abort_if($year < 2018 || $year > now()->year, 404);

        $disk = Storage::disk('local');
        $scans = Certificate::query()->where('year', $year)->whereNotNull('file_path')->orderBy('quarter')->orderBy('payor_name')->get()
            ->filter(fn (Certificate $c) => $disk->exists((string) $c->file_path));

        abort_if($scans->isEmpty(), 404);

        $path = (string) tempnam(sys_get_temp_dir(), 'eafs-');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);

        foreach ($scans as $certificate) {
            $extension = pathinfo((string) $certificate->original_filename, PATHINFO_EXTENSION) ?: pathinfo((string) $certificate->file_path, PATHINFO_EXTENSION);
            $zip->addFromString(
                sprintf('2307-%d-Q%d-%s-%d.%s', $year, $certificate->quarter, str($certificate->payor_name)->slug(), $certificate->id, $extension),
                (string) $disk->get((string) $certificate->file_path),
            );
        }

        $zip->close();

        return response()->download($path, "2307s-{$year}-for-eafs.zip", ['Content-Type' => 'application/zip'])->deleteFileAfterSend();
    }

    /**
     * Paying late (design 9-02): what the surcharge and interest add if paid
     * today, and what each further week adds. Only for an income tax return
     * past its due date and not yet filed.
     */
    public function late(Penalties $penalties, TaxCalculationService $engine, int $year, int $quarter, string $form): Response|RedirectResponse
    {
        $profile = $this->ensureForm($year, $quarter, $form);
        $snapshot = $this->returns->snapshot($profile, $year, $quarter, $form);

        if ($snapshot['penalties'] === null || $this->history->filingFor($year, $quarter, $form) !== null) {
            return redirect()->route('returns.show', [$year, $quarter, $form]);
        }

        return Inertia::render('returns/late', [
            'layout' => $this->returns->layout($snapshot),
            'dueDate' => $snapshot['figures']['due_date'],
            'weekly' => $penalties->weeklyInterest($snapshot['figures']['due'], CarbonImmutable::parse($snapshot['figures']['due_date']), $engine->forYear($profile, $year)['gross']),
            'abatement' => $this->abatement($profile, $snapshot),
        ]);
    }

    public function preview(OfficialForms $forms, int $year, int $quarter, string $form): Response
    {
        $profile = $this->ensureForm($year, $quarter, $form);
        $filing = $this->history->filingFor($year, $quarter, $form);
        $snapshot = $filing ? $this->returns->snapshotOf($filing, $profile) : $this->returns->snapshot($profile, $year, $quarter, $form);
        [, $quarterForms, $alternative] = $this->quarter($profile, $year, $quarter);

        return Inertia::render('returns/preview', [
            'layout' => $this->returns->layout($snapshot),
            'filedOn' => $filing?->filed_on->toDateString(),
            'missingDetails' => $forms->missingDetails($snapshot),
            'alternativeForm' => $quarter === 4 && $form !== $alternative ? $alternative : null,
            'paperFiling' => $this->paperFiling($form),
            // On graduated rates the 1701Q and the 2551Q go in together.
            'alsoFile' => array_map(fn (string $other) => [
                'form' => $other,
                'payable' => $this->returns->layout($this->returns->snapshot($profile, $year, $quarter, $other))['payable'],
            ], array_values(array_diff($quarterForms, [$form]))),
        ]);
    }

    /**
     * The return on the BIR's own form, filled in.
     */
    public function pdf(Request $request, OfficialForms $forms, int $year, int $quarter, string $form): HttpResponse
    {
        $profile = $this->ensureForm($year, $quarter, $form);
        $filing = $this->history->filingFor($year, $quarter, $form);
        $snapshot = $filing ? $this->returns->snapshotOf($filing, $profile) : $this->returns->snapshot($profile, $year, $quarter, $form);
        $name = "{$form}-{$year}-Q{$quarter}.pdf";

        return response($forms->render($snapshot), headers: [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('print') ? 'inline' : 'attachment')."; filename={$name}",
        ]);
    }

    /**
     * Only the forms this taxpayer actually files exist, and for the annual
     * return the older form the BIR still accepts.
     */
    private function ensureForm(int $year, int $quarter, string $form): TaxProfile
    {
        $profile = TaxProfile::current();
        [, $forms, $alternative] = $this->quarter($profile, $year, $quarter);

        if ($quarter === 4 && $alternative !== null) {
            $forms[] = $alternative;
        }

        abort_unless(
            in_array($form, $forms, true)
                && CarbonImmutable::create($year, $quarter * 3 - 2)->lessThanOrEqualTo(CarbonImmutable::now()),
            404,
        );

        return $profile;
    }

    /**
     * How a quarter is taxed (the year's method, on graduated rates once an
     * 8% year passed ₱3,000,000), the forms it's filed on, and for the
     * annual return the other form the BIR accepts.
     *
     * @return array{TaxProfile, list<string>, string|null}
     */
    private function quarter(TaxProfile $profile, int $year, int $quarter): array
    {
        $result = $this->engine->forYear($profile, $year);
        $taxpayer = $this->engine->taxpayerFor($profile, $result, $quarter);
        // A year on two methods has only the 1701.
        $alternative = $quarter === 4 && $result['switched'] === null
            ? $this->calendar->alternativeAnnualForm($taxpayer, TaxpayerClassification::from($result['classification']))
            : null;

        return [$taxpayer, $this->history->formsForQuarter($profile, $result, $quarter), $alternative];
    }
}
