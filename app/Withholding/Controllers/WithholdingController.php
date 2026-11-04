<?php

namespace App\Withholding\Controllers;

use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxProfile;
use App\Returns\Forms\Items\Form0619EItems;
use App\Returns\Forms\Items\Form1601EQItems;
use App\Returns\Forms\Items\Form1604EItems;
use App\Returns\Forms\Items\Form2307Items;
use App\Returns\Forms\OfficialFormFiller;
use App\Returns\Services\RecordsExport;
use App\Tax\Services\FilingCalendar;
use App\Withholding\Models\IssuedCertificate;
use App\Withholding\Services\Qap;
use App\Withholding\Services\WithholdingAgent;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * The taxpayer as a withholding agent: the year at a glance, each month's
 * 0619-E, each quarter's 1601-EQ with its list of payees, and the 2307 each
 * payee is owed.
 *
 * @phpstan-import-type Quarter from WithholdingAgent
 * @phpstan-import-type Payee from WithholdingAgent
 */
class WithholdingController extends Controller
{
    public function __construct(
        private WithholdingAgent $agent,
        private OfficialFormFiller $filler,
        private FilingCalendar $calendar,
    ) {}

    public function index(?int $year = null): Response
    {
        $year ??= now()->year;
        $this->ensure($year);
        $quarters = $this->agent->year($year);
        $payees = [];

        foreach ($quarters as $q) {
            foreach ($q['payees'] as $payee) {
                $payees[$payee['key']] ??= ['key' => $payee['key'], 'name' => $payee['name'], 'income' => 0.0, 'withheld' => 0.0, 'quarters' => []];
                $payees[$payee['key']]['income'] += $payee['income'];
                $payees[$payee['key']]['withheld'] += $payee['withheld'];
                $payees[$payee['key']]['quarters'][] = ['quarter' => $q['quarter'], 'given_on' => $payee['given_on']];
            }
        }

        return Inertia::render('withholding/index', [
            'year' => $year,
            'quarters' => array_values($quarters),
            'payees' => array_values($payees),
            'annualDue' => $this->calendar->withholdingAnnualDue($year)->toDateString(),
            'startedOn' => $this->agent->startedOn(TaxProfile::current())?->toDateString(),
        ]);
    }

    public function month(int $year, int $month): Response
    {
        $this->ensureMonth($year, $month);
        $quarter = (int) ceil($month / 3);
        $status = $this->agent->year($year)[$quarter]['months'][($month - 1) % 3];

        return Inertia::render('withholding/month', [
            'year' => $year,
            'month' => $month,
            'quarter' => $quarter,
            ...$this->agent->month($year, $month),
            'status' => $status,
            'agent' => $this->identity(),
            'edition' => (string) config('tax.forms.0619-E.edition'),
        ]);
    }

    public function monthPdf(int $year, int $month, Form0619EItems $items): HttpResponse
    {
        $this->ensureMonth($year, $month);
        $profile = TaxProfile::current();
        $due = $this->calendar->withholdingMonthlyDue($year, $month, $profile->efpsGroupFor($year, $month));
        $values = $items->values($profile, $year, $month, $this->agent->month($year, $month)['withheld'], $due);

        return $this->pdf('0619-E', $values, sprintf('0619-E-%d-%02d.pdf', $year, $month));
    }

    public function quarter(int $year, int $quarter, Qap $qap): Response
    {
        $this->ensureQuarter($year, $quarter);

        return Inertia::render('withholding/quarter', [
            'year' => $year,
            ...$this->agent->year($year)[$quarter],
            'qapFile' => $qap->fileName(TaxProfile::current(), $year, $quarter),
            'annualDue' => $this->calendar->withholdingAnnualDue($year)->toDateString(),
            'agent' => $this->identity(),
        ]);
    }

    public function quarterPreview(int $year, int $quarter): Response
    {
        $this->ensureQuarter($year, $quarter);

        return Inertia::render('withholding/quarter-form', [
            'year' => $year,
            ...$this->agent->year($year)[$quarter],
            'agent' => $this->identity(),
            'edition' => (string) config('tax.forms.1601-EQ.edition'),
        ]);
    }

    public function quarterPdf(int $year, int $quarter, Form1601EQItems $items): HttpResponse
    {
        $this->ensureQuarter($year, $quarter);

        return $this->pdf('1601-EQ', $items->values(TaxProfile::current(), $year, $this->agent->year($year)[$quarter]), "1601-EQ-{$year}-Q{$quarter}.pdf");
    }

    public function qap(int $year, int $quarter, string $kind, Qap $qap, RecordsExport $export): HttpResponse
    {
        $this->ensureQuarter($year, $quarter);
        $profile = TaxProfile::current();
        $q = $this->agent->year($year)[$quarter];

        if ($kind === 'csv') {
            return response($export->csvFile(['Payee', 'TIN', 'ATC', 'Income paid', 'Tax withheld'],
                array_map(fn (array $p) => [$p['name'], (string) $p['tin'], $p['atc'], $p['income'], $p['withheld']], $q['payees'])), headers: [
                    'Content-Type' => 'text/csv; charset=utf-8',
                    'Content-Disposition' => "attachment; filename=\"qap-{$year}-Q{$quarter}.csv\"",
                ]);
        }

        $name = $qap->fileName($profile, $year, $quarter) ?? abort(404);

        return response($qap->dat($profile, $year, $q), headers: [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$name}\"",
        ]);
    }

    /**
     * The year's list of payees, for the 1604-E (design 11-04).
     */
    public function annual(int $year, Qap $qap): Response
    {
        $this->ensure($year);

        return Inertia::render('withholding/annual', [
            ...$this->agent->annual($year),
            'alphalistFile' => $qap->annualFileName(TaxProfile::current(), $year),
            'agent' => $this->identity(),
        ]);
    }

    /**
     * The filled 1604-E (design 11-05): a draft until the year ends.
     */
    public function annualPreview(int $year): Response
    {
        $this->ensure($year);

        return Inertia::render('withholding/annual-form', [
            ...$this->agent->annual($year),
            'agent' => $this->identity(),
            'edition' => (string) config('tax.forms.1604-E.edition'),
        ]);
    }

    public function annualPdf(int $year, Form1604EItems $items): HttpResponse
    {
        $this->ensure($year);

        return $this->pdf('1604-E', $items->values(TaxProfile::current(), $this->agent->annual($year)), "1604-E-{$year}.pdf");
    }

    public function alphalist(int $year, string $kind, Qap $qap, RecordsExport $export): HttpResponse
    {
        $this->ensure($year);
        $profile = TaxProfile::current();
        $annual = $this->agent->annual($year);

        if ($kind === 'csv') {
            return response($export->csvFile(['Payee', 'TIN', 'ATC', 'Rate', 'Income paid', 'Tax withheld'],
                array_map(fn (array $p) => [$p['name'], (string) $p['tin'], $p['atc'], $p['rate'] * 100, $p['income'], $p['withheld']], $annual['payees'])), headers: [
                    'Content-Type' => 'text/csv; charset=utf-8',
                    'Content-Disposition' => "attachment; filename=\"alphalist-{$year}.csv\"",
                ]);
        }

        $name = $qap->annualFileName($profile, $year) ?? abort(404);

        return response($qap->annualDat($profile, $annual), headers: [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$name}\"",
        ]);
    }

    public function certificate(int $year, int $quarter, string $payee): Response
    {
        $this->ensureQuarter($year, $quarter);

        return Inertia::render('withholding/certificate', [
            'year' => $year,
            'quarter' => $quarter,
            'payee' => $this->payee($year, $quarter, $payee),
            'agent' => $this->identity(),
            'edition' => (string) config('tax.forms.2307.edition'),
        ]);
    }

    public function certificatePdf(int $year, int $quarter, string $payee, Form2307Items $items): HttpResponse
    {
        $this->ensureQuarter($year, $quarter);
        $p = $this->payee($year, $quarter, $payee);

        return $this->pdf('2307', $items->values(TaxProfile::current(), $year, $quarter, $p), "2307-{$year}-Q{$quarter}-{$p['key']}.pdf");
    }

    public function given(int $year, int $quarter, string $payee): RedirectResponse
    {
        $this->ensureQuarter($year, $quarter);
        $p = $this->payee($year, $quarter, $payee);

        IssuedCertificate::query()->firstOrCreate(
            ['year' => $year, 'quarter' => $quarter, 'payee' => $p['name']],
            ['given_on' => CarbonImmutable::now()->toDateString()],
        );

        return Inertia::flash('toast', "{$p['name']}’s 2307 is marked given.")->back();
    }

    /**
     * The taxpayer as the withholding agent named on the forms.
     *
     * @return array{name: string, tin: string|null, rdo: string|null, address: string|null}
     */
    private function identity(): array
    {
        $profile = TaxProfile::current();

        return [
            'name' => (string) ($profile->trade_name ?: $profile->full_name),
            'tin' => $profile->tin,
            'rdo' => $profile->rdo_code,
            'address' => $profile->registered_address,
        ];
    }

    /**
     * @return Payee
     */
    private function payee(int $year, int $quarter, string $key): array
    {
        foreach ($this->agent->year($year)[$quarter]['payees'] as $payee) {
            if ($payee['key'] === $key) {
                return $payee;
            }
        }

        abort(404);
    }

    /**
     * @param  array<array-key, string|int|float|bool|null>  $values
     */
    private function pdf(string $form, array $values, string $name): HttpResponse
    {
        return response($this->filler->render($form, $values), headers: [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => (request()->boolean('print') ? 'inline' : 'attachment')."; filename={$name}",
        ]);
    }

    private function ensure(int $year): void
    {
        abort_unless(TaxProfile::current()->withholding_agent && $year >= 2018 && $year <= now()->year, 404);
    }

    private function ensureMonth(int $year, int $month): void
    {
        $this->ensure($year);
        // December, March, June and September go on the 1601-EQ instead.
        abort_if($month < 1 || $month > 12 || $month % 3 === 0 || CarbonImmutable::create($year, $month)->isAfter(now()), 404);
    }

    private function ensureQuarter(int $year, int $quarter): void
    {
        $this->ensure($year);
        abort_if(CarbonImmutable::create($year, $quarter * 3 - 2)->isAfter(now()), 404);
    }
}
