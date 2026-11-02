<?php

namespace App\Books\Controllers;

use App\Books\Enums\BooksType;
use App\Books\Models\BooksMonth;
use App\Books\Requests\UpdateBooksTypeRequest;
use App\Books\Services\GeneralLedger;
use App\Books\Services\Journals;
use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Books of accounts: one month's cash receipts and disbursements journals,
 * and its general journal and ledger, to copy into the BIR-stamped books
 * or print on loose-leaf.
 */
class BooksController extends Controller
{
    public function __construct(private Journals $journals, private GeneralLedger $ledger) {}

    public function show(?int $year = null, ?int $month = null): Response
    {
        $today = CarbonImmutable::now();
        $period = $this->period($year ?? $today->year, $month ?? $today->month);
        $profile = TaxProfile::current();
        $next = $period->addMonth();

        return Inertia::render('books/show', [
            'period' => ['year' => $period->year, 'month' => $period->month],
            'previous' => $this->link($period->subMonth()),
            'next' => $next->isAfter($today) ? null : $this->link($next),
            'booksType' => ($profile->books_type ?? BooksType::Manual)->value,
            'regime' => $profile->regime?->value,
            'copiedAt' => BooksMonth::query()
                ->where('year', $period->year)
                ->where('month', $period->month)
                ->value('copied_at')
                ?->toDateString(),
            'retentionYears' => config('tax.books.retention_years'),
            ...$this->journals->forMonth($period->year, $period->month),
        ]);
    }

    public function type(UpdateBooksTypeRequest $request): RedirectResponse
    {
        TaxProfile::current()->update($request->validated());

        return back();
    }

    public function copied(int $year, int $month): RedirectResponse
    {
        $period = $this->period($year, $month);

        // Marking a month twice keeps the first date it was copied.
        BooksMonth::query()->firstOrCreate(
            ['year' => $period->year, 'month' => $period->month],
            ['copied_at' => CarbonImmutable::now()],
        );

        return Inertia::flash('toast', "{$period->format('F Y')} is marked copied into your books.")->back();
    }

    public function pdf(int $year, int $month): HttpResponse
    {
        $period = $this->period($year, $month);

        return $this->document('books.journals', $period, $this->journals->forMonth($period->year, $period->month))
            ->download(sprintf('books-%d-%02d.pdf', $period->year, $period->month));
    }

    public function general(int $year, int $month): Response
    {
        $today = CarbonImmutable::now();
        $period = $this->period($year, $month);
        $next = $period->addMonth();

        return Inertia::render('books/general', [
            'period' => ['year' => $period->year, 'month' => $period->month],
            'previous' => $this->link($period->subMonth()),
            'next' => $next->isAfter($today) ? null : $this->link($next),
            'booksType' => (TaxProfile::current()->books_type ?? BooksType::Manual)->value,
            'accounts' => array_map(fn (array $account) => $account[0], GeneralLedger::ACCOUNTS),
            'journal' => $this->ledger->journal($period->year, $period->month),
            'ledger' => $this->ledger->ledger($period->year, $period->month),
        ]);
    }

    public function generalPdf(int $year, int $month): HttpResponse
    {
        $period = $this->period($year, $month);

        return $this->document('books.general', $period, [
            'accounts' => array_map(fn (array $account) => $account[0], GeneralLedger::ACCOUNTS),
            'journal' => $this->ledger->journal($period->year, $period->month),
            'ledger' => $this->ledger->ledger($period->year, $period->month),
        ])->download(sprintf('general-journal-ledger-%d-%02d.pdf', $period->year, $period->month));
    }

    /**
     * A month's books as a page-numbered PDF.
     *
     * @param  array<string, mixed>  $data
     */
    private function document(string $view, CarbonImmutable $period, array $data): DomPdf
    {
        $profile = TaxProfile::current();
        $document = Pdf::loadView($view, [
            'period' => $period,
            'profile' => $profile,
            'booksType' => $profile->books_type ?? BooksType::Manual,
            'preparedOn' => CarbonImmutable::now(),
            ...$data,
        ])->setPaper('a4');

        // DomPDF can't count pages from CSS, so number them once laid out.
        $document->render();
        $dompdf = $document->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $canvas->page_text(
            $canvas->get_width() - 100,
            $canvas->get_height() - 28,
            'Page {PAGE_NUM} of {PAGE_COUNT}',
            $dompdf->getFontMetrics()->getFont('DejaVu Sans'),
            7,
            [0.28, 0.33, 0.41],
        );

        return $document;
    }

    private function period(int $year, int $month): CarbonImmutable
    {
        $today = CarbonImmutable::now();

        abort_if($month < 1 || $month > 12 || $year < 2018 || $year > $today->year, 404);

        $period = CarbonImmutable::create($year, $month);

        abort_if($period->isAfter($today), 404);

        return $period;
    }

    /**
     * @return array{year: int, month: int}|null
     */
    private function link(CarbonImmutable $period): ?array
    {
        return $period->year < 2018 ? null : ['year' => $period->year, 'month' => $period->month];
    }
}
