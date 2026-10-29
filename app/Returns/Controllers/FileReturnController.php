<?php

namespace App\Returns\Controllers;

use App\Compliance\Services\TaxObligations;
use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxProfile;
use App\Returns\Services\ReturnForm;
use App\Returns\Services\ReturnHistory;
use App\Tax\Services\FilingCalendar;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Preparing a return: the values to copy into eBIRForms, the filled form to
 * print, and marking it filed.
 */
class FileReturnController extends Controller
{
    public function __construct(
        private ReturnForm $returns,
        private ReturnHistory $history,
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

        return Inertia::render('returns/show', [
            'layout' => $layout,
            'dueDate' => $form === '2551Q'
                ? $calendar->percentageTaxDue($year, $quarter)->toDateString()
                : $snapshot['figures']['due_date'],
            'periodEnded' => $today->greaterThan($periodEnd),
            'periodEnd' => $periodEnd->toDateString(),
            'missingCertificates' => $form === '2551Q' ? [] : $obligations->checklist(
                $profile, $year, $snapshot['figures'], $transactions, $today,
            )['missing_certificates'],
            'otherForms' => array_values(array_diff($this->history->formsFor($profile, $quarter), [$form])),
        ]);
    }

    public function preview(int $year, int $quarter, string $form): Response
    {
        $profile = $this->ensureForm($year, $quarter, $form);
        $filing = $this->history->filingFor($year, $quarter, $form);
        $snapshot = $filing ? $this->returns->snapshotOf($filing, $profile) : $this->returns->snapshot($profile, $year, $quarter, $form);

        return Inertia::render('returns/preview', [
            'layout' => $this->returns->layout($snapshot),
            'filedOn' => $filing?->filed_on->toDateString(),
        ]);
    }

    public function pdf(Request $request, int $year, int $quarter, string $form): HttpResponse
    {
        $profile = $this->ensureForm($year, $quarter, $form);
        $filing = $this->history->filingFor($year, $quarter, $form);
        $snapshot = $filing ? $this->returns->snapshotOf($filing, $profile) : $this->returns->snapshot($profile, $year, $quarter, $form);

        $pdf = Pdf::loadView('returns.pdf', [
            'layout' => $this->returns->layout($snapshot),
            'filedOn' => $filing?->filed_on,
        ])->setPaper('a4');
        $name = "{$form}-{$year}-Q{$quarter}.pdf";

        return $request->boolean('print') ? $pdf->stream($name) : $pdf->download($name);
    }

    /**
     * Only the forms this taxpayer actually files exist.
     */
    private function ensureForm(int $year, int $quarter, string $form): TaxProfile
    {
        $profile = TaxProfile::current();

        abort_unless(
            in_array($form, $this->history->formsFor($profile, $quarter), true)
                && CarbonImmutable::create($year, $quarter * 3 - 2)->lessThanOrEqualTo(CarbonImmutable::now()),
            404,
        );

        return $profile;
    }
}
