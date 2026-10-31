<?php

namespace App\Returns\Services;

use App\Compliance\Models\Certificate;
use App\Profiles\Models\TaxProfile;
use App\Profiles\Resources\TaxProfileResource;
use App\Returns\Forms\OfficialForms;
use App\Returns\Models\Filing;
use App\Tax\Services\TaxCalculationService;
use App\Transactions\Enums\TransactionType;
use App\Transactions\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * The taxpayer's records as files they can hand to someone else: a tax
 * summary, a transactions spreadsheet, SAWT data per quarter, the filed
 * returns, and everything together in an encrypted ZIP.
 *
 * Every file says plainly that it comes from the taxpayer's own records.
 */
class RecordsExport
{
    public function __construct(
        private TaxCalculationService $engine,
        private ReturnForm $returns,
        private ReturnHistory $history,
        private OfficialForms $forms,
        private Sawt $sawt,
    ) {}

    public function taxSummary(TaxProfile $profile, int $year): PdfDocument
    {
        $today = CarbonImmutable::now();
        $result = $this->engine->forYear($profile, $year, $today);
        $filings = Filing::query()->where('year', $year)->orderBy('quarter')->orderBy('form')->get();
        $incomeFilings = $filings->whereIn('form', ReturnForm::INCOME_TAX_FORMS)->keyBy('quarter');

        return Pdf::loadView('reports.tax-summary', [
            'year' => $year,
            'profile' => TaxProfileResource::make($profile)->resolve(),
            'result' => $result,
            'incomeFilings' => $incomeFilings,
            'filings' => $filings,
            'paid' => (float) $filings->sum('amount_paid'),
            'preparedOn' => $today,
        ])->setPaper('a4');
    }

    /**
     * Every transaction in the year, one row each. Opens in Excel.
     */
    public function incomeCsv(int $year): string
    {
        $today = CarbonImmutable::now();
        $rows = Transaction::query()->forYear($year)->orderBy('occurred_on')->orderBy('id')->get()
            ->map(fn (Transaction $t) => [
                $t->occurred_on->toDateString(),
                'Q'.$t->quarter(),
                match ($t->type) {
                    TransactionType::Payment => 'Payment received',
                    TransactionType::Invoice => $t->invoice_paid ? 'Invoice (paid)' : 'Invoice (unpaid)',
                    TransactionType::Expense => 'Expense',
                },
                $t->counterparty,
                (string) $t->invoice_number,
                (string) ($t->category?->label() ?? $t->description),
                (float) $t->amount,
                (float) $t->tax_withheld,
                $t->certificateStatus($today)->value,
                (string) $t->certificate_received_on?->toDateString(),
                $t->type === TransactionType::Expense ? ($t->has_valid_invoice ? 'yes' : 'no') : '',
            ]);

        return $this->csv([
            'Date', 'Quarter', 'Type', 'Client / payee', 'Invoice no.', 'Category / description',
            'Amount', 'Tax withheld', '2307 status', '2307 received on', 'Valid invoice (expenses)',
        ], $rows);
    }

    /**
     * Summary Alphalist of Withholding Taxes data for one quarter: one row
     * per client who withheld tax, to read or enter by hand. The file the
     * BIR takes is Sawt::dat().
     */
    public function sawtCsv(int $year, int $quarter): string
    {
        $rows = array_map(fn (array $row) => [
            $row['seq'],
            (string) $row['tin'],
            $row['payor'],
            (string) $row['atc'],
            $row['income'],
            $row['rate'],
            $row['withheld'],
            $row['certificate'] ? '2307 on file' : '2307 not on file',
        ], $this->sawt->rows($year, $quarter));

        return $this->csv([
            'Seq no.', 'Payor TIN', 'Payor name', 'ATC', 'Income payment', 'Tax rate (%)', 'Tax withheld', '2307',
        ], $rows);
    }

    /**
     * @return list<int> quarters of the year in which a client withheld tax
     */
    public function sawtQuarters(int $year): array
    {
        return array_values(Transaction::query()->forYear($year)->where('tax_withheld', '>', 0)->get()
            ->map(fn (Transaction $t) => $t->quarter())
            ->unique()
            ->sort()
            ->values()
            ->all());
    }

    /**
     * Everything for one year in a ZIP encrypted with the given password.
     * Returns the path of a temporary file the caller sends and deletes.
     */
    public function package(TaxProfile $profile, int $year, string $password): string
    {
        $path = tempnam(sys_get_temp_dir(), 'buwisbuddy-');
        $zip = new ZipArchive;

        if ($path === false || $zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the export file.');
        }

        $add = function (string $name, string $contents) use ($zip, $password) {
            $zip->addFromString($name, $contents);
            $zip->setEncryptionName($name, ZipArchive::EM_AES_256, $password);
        };

        $add('README.txt', $this->readme($profile, $year));
        $add("tax-summary-{$year}.pdf", $this->taxSummary($profile, $year)->output());
        $add("income-and-withholding-{$year}.csv", $this->incomeCsv($year));

        foreach ($this->sawtQuarters($year) as $quarter) {
            $add("sawt/sawt-{$year}-Q{$quarter}.csv", $this->sawtCsv($year, $quarter));

            if (($name = $this->sawt->fileName($profile, $year, $quarter)) !== null) {
                $add("sawt/{$name}", $this->sawt->dat($profile, $year, $quarter));
            }
        }

        foreach (Filing::query()->where('year', $year)->get() as $filing) {
            $add("returns/{$filing->form}-{$year}-Q{$filing->quarter}.pdf", $this->forms->render($this->returns->snapshotOf($filing, $profile)));
        }

        foreach (Certificate::query()->where('year', $year)->whereNotNull('file_path')->get() as $certificate) {
            $disk = Storage::disk('local');

            if ($certificate->file_path && $disk->exists($certificate->file_path)) {
                $extension = pathinfo((string) $certificate->original_filename, PATHINFO_EXTENSION) ?: 'pdf';
                $add(
                    sprintf('2307s/Q%d-%s-%d.%s', $certificate->quarter, str($certificate->payor_name)->slug(), $certificate->id, $extension),
                    (string) $disk->get($certificate->file_path),
                );
            }
        }

        $zip->close();

        return $path;
    }

    private function readme(TaxProfile $profile, int $year): string
    {
        $entries = collect($this->history->entries($profile, CarbonImmutable::now()))->where('year', $year);
        $filed = $entries->where('status', 'filed')->count();

        return implode("\r\n", [
            "Tax records for {$year}: {$profile->full_name}".($profile->tin ? ", TIN {$profile->tin}" : ''),
            'Prepared by BuwisBuddy on '.now()->format('F j, Y').' from the taxpayer\'s own records.',
            '',
            "tax-summary-{$year}.pdf              Income, tax and filings for the year",
            "income-and-withholding-{$year}.csv   Every payment, invoice and expense (opens in Excel)",
            'sawt/                                 2307 data per quarter: a CSV to read, and the .DAT file for the BIR',
            "returns/                              The {$filed} filed returns, as filed, on the BIR's own forms",
            '2307s/                                Certificates from clients, as received',
            '',
            'Figures follow rule pack '.config('tax.version').'. Returns are filled from these records and are not signed.',
        ])."\r\n";
    }

    /**
     * @param  list<string>  $header
     * @param  iterable<array<int, string|int|float>>  $rows
     */
    private function csv(array $header, iterable $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new RuntimeException('Could not build the CSV.');
        }

        fputcsv($handle, $header, escape: '');

        foreach ($rows as $row) {
            fputcsv($handle, array_map($this->safeCell(...), $row), escape: '');
        }

        rewind($handle);
        $contents = (string) stream_get_contents($handle);
        fclose($handle);

        // The byte-order mark makes Excel read the file as UTF-8 (₱, ñ).
        return "\u{FEFF}".$contents;
    }

    /**
     * Client names are typed by people; a name starting with "=" must not run
     * as a formula when the file is opened in a spreadsheet.
     */
    private function safeCell(string|int|float $value): string|int|float
    {
        return is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'{$value}" : $value;
    }
}
