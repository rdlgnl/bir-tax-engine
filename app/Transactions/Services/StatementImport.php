<?php

namespace App\Transactions\Services;

use App\Compliance\Models\Certificate;
use App\Returns\Models\Filing;
use App\Tax\Services\TaxRules;
use App\Transactions\Enums\TransactionType;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Reading a bank, e-wallet or payout statement (CSV) and sorting its lines:
 * client payments to import, money that isn't income, payments already
 * recorded, and lines only the taxpayer can answer.
 *
 * A statement shows the cash that arrived, after any tax a client withheld.
 * A client who has withheld from every recent payment is assumed to have
 * withheld again at the same rate, so the payment is recorded at the amount
 * billed, as the rest of the app counts sales.
 *
 * @phpstan-type Mapping array{date: int|null, description: int|null, amount: int|null, credit: int|null, debit: int|null}
 * @phpstan-type Line array{line: int, date: string|null, description: string, amount: float|null}
 * @phpstan-type Statement array{columns: list<string>, mapping: Mapping, lines: list<Line>, matched: bool}
 * @phpstan-type Row array{index: int, date: string|null, description: string, received: float, status: 'import'|'settles'|'ignore'|'duplicate'|'answer'|'filed'|'invalid', client: string|null, amount: float, withheld: float, reason: string, invoice?: int}
 */
class StatementImport
{
    public function __construct(private CsvFile $csv, private TaxRules $rules) {}

    private const COLUMNS = [
        'date' => ['date', 'transaction date', 'txn date', 'trans date', 'posting date', 'posted', 'date posted', 'value date', 'completed date'],
        'description' => ['description', 'transaction description', 'transaction details', 'details', 'particulars', 'narration', 'remarks', 'memo', 'reference', 'notes', 'note', 'name', 'payee', 'type'],
        'amount' => ['amount', 'amount php', 'net amount', 'transaction amount', 'value'],
        'credit' => ['credit', 'credits', 'credit amount', 'deposit', 'deposits', 'money in', 'cash in', 'in'],
        'debit' => ['debit', 'debits', 'debit amount', 'withdrawal', 'withdrawals', 'money out', 'cash out', 'out'],
    ];

    /**
     * The statement's columns, which of them hold the date, description and
     * money, and every line read with them.
     *
     * @param  Mapping|null  $mapping  the taxpayer's own choice of columns
     * @return Statement
     */
    public function read(string $path, ?array $mapping = null): array
    {
        $records = $this->csv->records($path);
        [$headerAt, $guessed] = $this->findHeader($records);
        $columns = array_map(fn ($c) => trim((string) $c), $records[$headerAt] ?? []);
        $mapping ??= $guessed;
        $matched = $mapping['date'] !== null && $mapping['description'] !== null
            && ($mapping['amount'] !== null || $mapping['credit'] !== null || $mapping['debit'] !== null);

        $lines = [];

        foreach (array_slice($records, $headerAt + 1, (int) config('import.max_rows'), true) as $i => $record) {
            if (! $matched || count(array_filter($record, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $amount = $mapping['amount'] !== null
                ? $this->csv->money($record[$mapping['amount']] ?? '')
                : (($this->csv->money($record[$mapping['credit']] ?? '') ?? 0.0) - abs($this->csv->money($record[$mapping['debit']] ?? '') ?? 0.0));

            $lines[] = [
                'line' => $i + 1,
                'date' => $this->csv->date((string) ($record[$mapping['date']] ?? '')),
                'description' => Str::squish((string) ($record[$mapping['description']] ?? '')),
                'amount' => $amount,
            ];
        }

        return ['columns' => $columns, 'mapping' => $mapping, 'lines' => $lines, 'matched' => $matched];
    }

    /**
     * Sorts each line, money in first.
     *
     * @param  list<Line>  $lines
     * @return list<Row>
     */
    public function classify(array $lines, CarbonImmutable $today): array
    {
        $clients = $this->clients();
        $income = Transaction::query()->where('type', '!=', TransactionType::Expense)->get();
        $window = (int) config('import.duplicate_window_days');
        $rows = [];
        // Invoices an earlier line of this statement already pays.
        $settling = [];

        foreach ($lines as $index => $line) {
            $received = (float) ($line['amount'] ?? 0);
            $row = [
                'index' => $index, 'date' => $line['date'], 'description' => $line['description'], 'received' => $received,
                'status' => 'ignore', 'client' => null, 'amount' => $received, 'withheld' => 0.0, 'reason' => '',
            ];

            if ($line['date'] === null || $line['amount'] === null) {
                $rows[] = [...$row, 'status' => 'invalid', 'reason' => 'Date or amount not readable'];

                continue;
            }

            $date = CarbonImmutable::parse($line['date']);

            if ($received <= 0) {
                $rows[] = [...$row, 'reason' => 'Money out'];

                continue;
            }

            if ($date->greaterThan($today)) {
                $rows[] = [...$row, 'status' => 'invalid', 'reason' => 'Dated in the future'];

                continue;
            }

            if ($date->lessThan($this->rules->firstDate())) {
                $rows[] = [...$row, 'status' => 'invalid', 'reason' => 'Before '.$this->rules->firstDate()->year.', when the app\'s tax rules start'];

                continue;
            }

            $text = $this->normalise($line['description']);

            if ($this->containsAny($text, (array) config('import.not_income'))) {
                $rows[] = [...$row, 'reason' => 'Not income (interest, refund or reversal)'];

                continue;
            }

            $client = $this->matchClient($text, $clients);
            $duplicate = $income->first(fn (Transaction $t) => abs($t->occurred_on->diffInDays($date, false)) <= $window
                && ($client === null || $this->normalise($t->counterparty) === $this->normalise($client))
                && (abs((float) $t->amount - (float) $t->tax_withheld - $received) < 0.01 || abs((float) $t->amount - $received) < 0.01));

            if ($duplicate !== null) {
                $rows[] = [...$row, 'status' => 'duplicate', 'client' => $duplicate->counterparty,
                    'reason' => 'Recorded on '.$duplicate->occurred_on->format('M j')];

                continue;
            }

            if ($client === null) {
                $rows[] = [...$row, 'status' => 'answer', 'reason' => 'Not a client we know'];

                continue;
            }

            // Money for an invoice already recorded settles it: the sale counts on the invoice's date.
            $invoice = $this->unpaidInvoice($client, $received, $date, $income, $settling);

            if ($invoice !== null) {
                $settling[] = $invoice->id;
                $rows[] = [...$row, 'status' => 'settles', 'client' => $invoice->counterparty, 'amount' => (float) $invoice->amount,
                    'withheld' => round(max(0.0, (float) $invoice->amount - $received), 2), 'invoice' => $invoice->id,
                    'reason' => "Pays invoice {$invoice->invoice_number} of {$invoice->occurred_on->format('M j')}"];

                continue;
            }

            if (Filing::incomeTaxReturnCovering($date) !== null) {
                $rows[] = [...$row, 'status' => 'filed', 'client' => $client, 'reason' => 'Its quarter is already filed'];

                continue;
            }

            [$amount, $withheld, $rate] = $this->grossUp($client, $received, $income);
            $rows[] = [...$row, 'status' => 'import', 'client' => $client, 'amount' => $amount, 'withheld' => $withheld,
                'reason' => $rate > 0 ? sprintf('%s usually withholds %s%%', $client, rtrim(rtrim(number_format($rate * 100, 1), '0'), '.')) : ''];
        }

        return $rows;
    }

    /**
     * Records the payments: the ones sorted for import, and the ones the
     * taxpayer said were client payments.
     *
     * @param  list<Row>  $rows
     * @param  array<int, array{kind: string, client?: string|null}>  $answers
     * @return array{imported: int, settled: int, total: float, skipped: int}
     */
    public function import(array $rows, array $answers, CarbonImmutable $today): array
    {
        $income = Transaction::query()->where('type', '!=', TransactionType::Expense)->get();
        $imported = 0;
        $settled = 0;
        $skipped = 0;
        $total = 0.0;

        foreach ($rows as $row) {
            $answer = $answers[$row['index']] ?? null;

            if ($row['status'] === 'settles' && isset($row['invoice'])) {
                $invoice = Transaction::query()->whereKey($row['invoice'])->where('type', TransactionType::Invoice)->where('invoice_paid', false)->first();

                if ($invoice !== null) {
                    $invoice->update([
                        'invoice_paid' => true,
                        'invoice_paid_on' => $row['date'],
                        'tax_withheld' => (float) $invoice->tax_withheld > 0 ? $invoice->tax_withheld : $row['withheld'],
                    ]);
                    $settled++;
                }

                continue;
            }

            if ($row['status'] === 'answer' && ($answer['kind'] ?? null) === 'payment' && filled($answer['client'] ?? null)) {
                $client = Str::squish((string) $answer['client']);

                if (Filing::incomeTaxReturnCovering(CarbonImmutable::parse((string) $row['date'])) !== null) {
                    $skipped++;

                    continue;
                }

                [$amount, $withheld] = $this->grossUp($client, $row['received'], $income);
                $row = [...$row, 'status' => 'import', 'client' => $client, 'amount' => $amount, 'withheld' => $withheld];
            }

            if ($row['status'] !== 'import' || $row['client'] === null) {
                continue;
            }

            Transaction::query()->create([
                'type' => TransactionType::Payment,
                'occurred_on' => $row['date'],
                'counterparty' => $row['client'],
                'amount' => $row['amount'],
                'tax_withheld' => $row['withheld'],
                'description' => Str::limit('Imported: '.$row['description'], 250, ''),
            ]);
            $imported++;
            $total += $row['amount'];
        }

        return ['imported' => $imported, 'settled' => $settled, 'total' => round($total, 2), 'skipped' => $skipped];
    }

    /**
     * An unpaid invoice from the client this money pays: the full amount,
     * or the amount less what the client plausibly withheld (up to 15%),
     * issued in the six months before.
     *
     * @param  Collection<int, Transaction>  $income
     * @param  list<int>  $taken  invoices already paid by other lines
     */
    private function unpaidInvoice(string $client, float $received, CarbonImmutable $date, Collection $income, array $taken): ?Transaction
    {
        return $income
            ->filter(fn (Transaction $t) => $t->type === TransactionType::Invoice
                && ! $t->invoice_paid
                && ! in_array($t->id, $taken, true)
                && $this->normalise($t->counterparty) === $this->normalise($client)
                && $t->occurred_on->lessThanOrEqualTo($date)
                && $t->occurred_on->greaterThanOrEqualTo($date->subMonths(6))
                && $received <= (float) $t->amount + 0.005
                && (float) $t->amount - $received <= (float) $t->amount * 0.15 + 0.005)
            ->sortBy('occurred_on')
            ->first();
    }

    /**
     * Clients the app knows, with payout platforms after them.
     *
     * @return array<string, string> normalised name => name as recorded
     */
    private function clients(): array
    {
        $names = Transaction::query()->where('type', '!=', TransactionType::Expense)->distinct()->pluck('counterparty')
            ->merge(Certificate::query()->distinct()->pluck('payor_name'));
        $clients = [];

        foreach ($names as $name) {
            $key = $this->withoutCompanyWords($this->normalise((string) $name));

            if ($key !== '') {
                $clients[$key] ??= (string) $name;
            }
        }

        // Longer names first, so "LUMEN DIGITAL" wins over "LUMEN".
        uksort($clients, fn ($a, $b) => strlen($b) <=> strlen($a));

        foreach ((array) config('import.platforms') as $word => $name) {
            $clients[$this->normalise((string) $word)] ??= (string) $name;
        }

        return $clients;
    }

    /**
     * @param  array<string, string>  $clients
     */
    private function matchClient(string $text, array $clients): ?string
    {
        foreach ($clients as $key => $name) {
            if (str_contains(" {$text} ", " {$key} ")) {
                return $name;
            }
        }

        return null;
    }

    /**
     * The amount billed and the tax withheld, for cash received from a
     * client who has withheld at one rate from each of their last payments.
     *
     * @param  Collection<int, Transaction>  $income
     * @return array{float, float, float}
     */
    private function grossUp(string $client, float $received, Collection $income): array
    {
        $recent = $income->filter(fn (Transaction $t) => $this->normalise($t->counterparty) === $this->normalise($client))
            ->sortByDesc('occurred_on')->take(3);
        $rates = $recent->map(fn (Transaction $t) => (float) $t->amount > 0 ? round((float) $t->tax_withheld / (float) $t->amount, 3) : 0.0)->unique();

        if ($recent->isEmpty() || $rates->count() !== 1 || $rates->first() <= 0 || $rates->first() >= 0.5) {
            return [$received, 0.0, 0.0];
        }

        $rate = (float) $rates->first();
        $amount = round($received / (1 - $rate), 2);

        return [$amount, round($amount - $received, 2), $rate];
    }

    /**
     * The first of the opening lines whose cells read as column names.
     *
     * @param  list<list<string|null>>  $records
     * @return array{int, Mapping}
     */
    private function findHeader(array $records): array
    {
        $empty = ['date' => null, 'description' => null, 'amount' => null, 'credit' => null, 'debit' => null];

        foreach (array_slice($records, 0, 15, true) as $i => $record) {
            $mapping = $empty;
            $names = array_map(fn ($v) => Str::of((string) $v)->lower()->replaceMatches('/[^a-z ]+/', ' ')->squish()->value(), $record);

            foreach (self::COLUMNS as $field => $synonyms) {
                foreach ($synonyms as $synonym) {
                    $at = array_search($synonym, $names, true);

                    if ($at !== false && ! in_array($at, $mapping, true)) {
                        $mapping[$field] = (int) $at;
                        break;
                    }
                }
            }

            if ($mapping['date'] !== null && ($mapping['amount'] !== null || $mapping['credit'] !== null)) {
                return [$i, $mapping];
            }
        }

        return [0, $empty];
    }

    private function normalise(string $value): string
    {
        return Str::of($value)->upper()->replaceMatches('/[^A-Z0-9]+/', ' ')->squish()->value();
    }

    private function withoutCompanyWords(string $name): string
    {
        $words = array_diff(explode(' ', $name), (array) config('import.company_words'));

        return implode(' ', $words);
    }

    /**
     * @param  array<mixed>  $words
     */
    private function containsAny(string $text, array $words): bool
    {
        foreach ($words as $word) {
            if (str_contains(" {$text} ", ' '.$this->normalise((string) $word).' ')) {
                return true;
            }
        }

        return false;
    }
}
