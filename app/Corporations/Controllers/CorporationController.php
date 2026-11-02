<?php

namespace App\Corporations\Controllers;

use App\Corporations\Models\Corporation;
use App\Corporations\Models\CorporationQuarter;
use App\Corporations\Requests\MarkQuarterFiledRequest;
use App\Corporations\Requests\SaveCorporationRequest;
use App\Corporations\Requests\SaveQuarterRequest;
use App\Corporations\Services\CorporateTax;
use App\Http\Controllers\Controller;
use App\Returns\Forms\Items\Form1702QItems;
use App\Returns\Forms\Items\Form1702RTItems;
use App\Returns\Forms\OfficialFormFiller;
use App\Tax\Services\FilingCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Number;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * A corporation the taxpayer runs (design 13-08): its year so far from the
 * quarterly figures in its books, the 1702Q for each quarter (13-09) on the
 * BIR's own form (13-10), the year's 1702-RT (13-11), and the other returns
 * it files.
 */
class CorporationController extends Controller
{
    public function __construct(private CorporateTax $tax, private FilingCalendar $calendar) {}

    public function show(): Response|RedirectResponse
    {
        $corporation = Corporation::current();

        if ($corporation === null) {
            return redirect()->route('corporation.edit');
        }

        $today = CarbonImmutable::now();
        $quarters = $this->tax->quarters($corporation, $today->year);
        $next = $this->tax->nextQuarter($corporation, $today);
        $latest = collect($quarters)->filter(fn (array $quarter) => $quarter['has_figures'])->last();
        $previous = $this->tax->annual($corporation, $today->year - 1);

        return Inertia::render('corporation/show', [
            'corporation' => $this->resource($corporation),
            'year' => $today->year,
            'next' => $next !== null ? $quarters[$next] : null,
            'soFar' => $latest,
            // Last year's 1702-RT until it's filed, then this year's estimate.
            'annual' => $previous !== null && $previous['status'] !== 'filed' ? $previous : $this->tax->annual($corporation, $today->year),
            'quarters' => array_values($quarters),
            'rateNote' => $this->rateNote($latest['small'] ?? null, $corporation),
            'mcitNote' => $this->mcitNote($corporation, $today->year),
            'mcitRate' => $this->tax->mcitRate($today->year, $today->quarter),
            'alsoDue' => $this->alsoDue($corporation, $today),
        ]);
    }

    public function edit(): Response
    {
        $corporation = Corporation::current();

        return Inertia::render('corporation/edit', [
            'corporation' => $corporation !== null ? $this->resource($corporation) : null,
        ]);
    }

    public function save(SaveCorporationRequest $request): RedirectResponse
    {
        $corporation = Corporation::current() ?? new Corporation;
        $corporation->fill($request->validated())->save();

        Inertia::flash('toast', 'Corporation saved.');

        return redirect()->route('corporation.show');
    }

    public function destroy(): RedirectResponse
    {
        Corporation::current()?->delete();
        Inertia::flash('toast', 'Corporation removed.');

        return redirect()->route('home');
    }

    public function quarter(int $year, int $quarter): Response
    {
        $corporation = $this->corporation();

        return Inertia::render('corporation/quarter', [
            'corporation' => $this->resource($corporation),
            'tax' => $this->tax->quarter($corporation, $year, $quarter),
            'figures' => $this->row($corporation, $year, $quarter)?->only(['sales', 'cost_of_sales', 'other_income', 'deductions', 'withheld']),
            'mcitRate' => $this->tax->mcitRate($year, $quarter),
        ]);
    }

    public function saveQuarter(SaveQuarterRequest $request, int $year, int $quarter): RedirectResponse
    {
        abort_if($year > CarbonImmutable::now()->year || ! $this->tax->covers($year), 404);
        $corporation = $this->corporation();
        $corporation->quarters()->updateOrCreate(['year' => $year, 'quarter' => $quarter], $request->validated());

        return Inertia::flash('toast', "Q{$quarter} figures saved.")->back();
    }

    public function form(int $year, int $quarter): Response
    {
        $corporation = $this->corporation();

        return Inertia::render('corporation/form', [
            'corporation' => $this->resource($corporation),
            'tax' => $this->tax->quarter($corporation, $year, $quarter),
            'edition' => (string) config('tax.forms.1702Q.edition'),
            'mcitRate' => $this->tax->mcitRate($year, $quarter),
            'annualDue' => $this->calendar->incomeTaxDue($year, 4)->toDateString(),
        ]);
    }

    public function pdf(Request $request, int $year, int $quarter, Form1702QItems $items, OfficialFormFiller $filler): HttpResponse
    {
        $corporation = $this->corporation();
        $values = $items->values($corporation, $this->tax->quarter($corporation, $year, $quarter));

        return response($filler->render('1702Q', $values), headers: [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('print') ? 'inline' : 'attachment')."; filename=1702Q-{$year}-Q{$quarter}.pdf",
        ]);
    }

    public function filed(MarkQuarterFiledRequest $request, int $year, int $quarter): RedirectResponse
    {
        $row = $this->row($this->corporation(), $year, $quarter);
        abort_if($row === null, 404);
        $row->forceFill($request->validated())->save();

        return Inertia::flash('toast', "Q{$quarter} 1702Q is marked filed.")->back();
    }

    /**
     * The year's 1702-RT (design 13-11), estimated until the 4th quarter's
     * figures are in.
     */
    public function annual(int $year): Response
    {
        $corporation = $this->corporation();
        $annual = $this->tax->annual($corporation, $year);
        abort_if($annual === null, 404);

        return Inertia::render('corporation/annual', [
            'corporation' => $this->resource($corporation),
            'annual' => $annual,
            'figures' => $this->row($corporation, $year, 4)?->only(['sales', 'cost_of_sales', 'other_income', 'deductions', 'withheld']),
            'edition' => (string) config('tax.forms.1702-RT.edition'),
            'mcitRate' => $this->tax->mcitAverageRate($year),
        ]);
    }

    public function annualPdf(Request $request, int $year, Form1702RTItems $items, OfficialFormFiller $filler): HttpResponse
    {
        $corporation = $this->corporation();
        $annual = $this->tax->annual($corporation, $year);
        abort_if($annual === null, 404);

        return response($filler->render('1702-RT', $items->values($corporation, $annual)), headers: [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('print') ? 'inline' : 'attachment')."; filename=1702-RT-{$year}.pdf",
        ]);
    }

    /**
     * The 1702-RT is filed on the year's final figures, so the 4th
     * quarter's have to be in.
     */
    public function annualFiled(MarkQuarterFiledRequest $request, int $year): RedirectResponse
    {
        $row = $this->row($this->corporation(), $year, 4);
        abort_if($row === null, 404);
        $row->forceFill($request->validated())->save();

        return Inertia::flash('toast', "The {$year} 1702-RT is marked filed.")->back();
    }

    private function corporation(): Corporation
    {
        return Corporation::current() ?? abort(404);
    }

    private function row(Corporation $corporation, int $year, int $quarter): ?CorporationQuarter
    {
        return $corporation->quarters()->where('year', $year)->where('quarter', $quarter)->first();
    }

    /**
     * Why the rate is 20% or 25%.
     *
     * @return array{title: string, body: string}|null
     */
    private function rateNote(?bool $small, Corporation $corporation): ?array
    {
        if ($small === null) {
            return null;
        }

        $income = '₱'.Number::abbreviate((float) config('tax.corporation.small_taxable_income'));
        $assets = '₱'.Number::abbreviate((float) config('tax.corporation.small_total_assets'));

        return $small
            ? ['title' => 'Why 20%, not 25%', 'body' => "Taxable income is {$income} or less and total assets are {$assets} or less (excluding land). Above either, the rate is 25%."]
            : ['title' => 'Why 25%, not 20%', 'body' => (float) $corporation->total_assets > (float) config('tax.corporation.small_total_assets')
                ? "Total assets (excluding land) are above {$assets}. The 20% rate is for corporations with {$assets} or less and taxable income of {$income} or less."
                : "Taxable income so far is above {$income}. The 20% rate is for {$income} or less, with total assets of {$assets} or less (excluding land)."];
    }

    /**
     * Whether the minimum corporate income tax applies this year, and why.
     *
     * @return array{applies: bool, title: string, body: string}
     */
    private function mcitNote(Corporation $corporation, int $year): array
    {
        $from = $this->tax->mcitFrom($corporation);
        $rate = rtrim(rtrim(number_format($this->tax->mcitAverageRate($year) * 100, 2), '0'), '.').'%';

        if ($year < $from) {
            return [
                'applies' => false,
                'title' => "Minimum corporate income tax starts in {$from}",
                'body' => "It applies from the 4th year after the year it registered with the BIR ({$corporation->operations_began}). Until then, only the regular rate applies.",
            ];
        }

        $nth = Number::ordinal($year - $corporation->operations_began);

        return [
            'applies' => true,
            'title' => "Minimum corporate income tax applies from {$from}",
            'body' => "{$year} is the {$nth} year after it registered with the BIR ({$corporation->operations_began}), so each quarter compares {$rate} of gross income with the regular tax.",
        ];
    }

    /**
     * The corporation's other returns, next due: VAT, withholding on its
     * suppliers and its payroll. Reminders only; the app files these for
     * the taxpayer, not the corporation.
     *
     * @return list<array{title: string, detail: string, date: string}>
     */
    private function alsoDue(Corporation $corporation, CarbonImmutable $today): array
    {
        $lastQuarter = $today->subMonthsNoOverflow(3);
        $lastMonth = $today->subMonthNoOverflow();
        $candidates = [];

        if ($corporation->vat_registered) {
            $candidates[] = array_map(fn (CarbonImmutable $date) => [
                'title' => "Q{$date->quarter} VAT (2550Q)",
                'detail' => 'Output VAT less input VAT',
                'date' => $this->calendar->vatDue($date->year, $date->quarter),
            ], [$lastQuarter, $today]);
        }

        if ($corporation->withholding_agent) {
            $candidates[] = array_map(fn (CarbonImmutable $month) => $month->month % 3 === 0
                ? ['title' => "Q{$month->quarter} withholding (1601-EQ)", 'detail' => 'Payments to suppliers', 'date' => $this->calendar->withholdingQuarterlyDue($month->year, $month->quarter)]
                : ['title' => "{$month->format('F')} withholding (0619-E)", 'detail' => 'Payments to suppliers', 'date' => $this->calendar->withholdingMonthlyDue($month->year, $month->month)],
                [$lastMonth, $today]);
        }

        if ($corporation->employees > 0) {
            $candidates[] = array_map(fn (CarbonImmutable $month) => [
                'title' => "{$month->format('F')} payroll (1601-C)",
                'detail' => $corporation->employees === 1 ? '1 employee' : "{$corporation->employees} employees",
                'date' => $this->calendar->compensationMonthlyDue($month->year, $month->month),
            ], [$lastMonth, $today]);
        }

        // For each return, the earliest period still to come due.
        $items = [];

        foreach ($candidates as $periods) {
            foreach ($periods as $period) {
                if ($period['date']->greaterThanOrEqualTo($today->startOfDay())) {
                    $items[] = [...$period, 'date' => $period['date']->toDateString()];
                    break;
                }
            }
        }

        usort($items, fn (array $a, array $b) => strcmp($a['date'], $b['date']));

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function resource(Corporation $corporation): array
    {
        return [
            'id' => $corporation->id,
            'name' => $corporation->name,
            'tin' => $corporation->tin,
            'rdo_code' => $corporation->rdo_code,
            'registered_address' => $corporation->registered_address,
            'zip_code' => $corporation->zip_code,
            'contact_number' => $corporation->contact_number,
            'email' => $corporation->email,
            'operations_began' => $corporation->operations_began,
            'total_assets' => (float) $corporation->total_assets,
            'deduction_method' => $corporation->deduction_method,
            'vat_registered' => $corporation->vat_registered,
            'withholding_agent' => $corporation->withholding_agent,
            'employees' => $corporation->employees,
        ];
    }
}
