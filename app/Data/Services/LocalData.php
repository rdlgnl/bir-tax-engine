<?php

namespace App\Data\Services;

use App\Compliance\Models\Certificate;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Everything the app keeps about the taxpayer on this device: the rows of
 * its tables (a corporation it runs among them), and the documents (2307 and
 * 2316 scans, BIR letters) on the local disk.
 *
 * @phpstan-type Summary array{records: int, first_year: int|null, last_year: int|null, certificates: int, documents: int, filings: int}
 */
class LocalData
{
    /**
     * The taxpayer's tables, parents before the tables that point to them.
     * Device settings (app_settings) are not records and stay out of backups.
     */
    public const TABLES = ['tax_profiles', 'tax_years', 'certificates', 'transactions', 'filings', 'books_months', 'issued_certificates', 'compensation_certificates', 'loans', 'employees', 'employee_certificates', 'bir_letters', 'corporations', 'corporation_quarters', 'record_changes'];

    /** Where documents live on the local disk. */
    public const DOCUMENTS = 'certificates';

    /**
     * @return Summary
     */
    public function summary(): array
    {
        $first = Transaction::query()->min('occurred_on');
        $last = Transaction::query()->max('occurred_on');

        return [
            'records' => Transaction::query()->count(),
            'first_year' => $first ? CarbonImmutable::parse($first)->year : null,
            'last_year' => $last ? CarbonImmutable::parse($last)->year : null,
            'certificates' => Certificate::query()->count(),
            'documents' => count($this->documents()),
            'filings' => DB::table('filings')->count(),
        ];
    }

    /**
     * Every row of every table, as stored.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function rows(): array
    {
        $tables = [];

        foreach (self::TABLES as $table) {
            $tables[$table] = array_values(DB::table($table)->orderBy($this->key($table))->get()
                ->map(fn (object $row) => (array) $row)
                ->all());
        }

        return $tables;
    }

    /**
     * Paths of the documents on the local disk.
     *
     * @return list<string>
     */
    public function documents(): array
    {
        return array_values(Storage::disk('local')->allFiles(self::DOCUMENTS));
    }

    /**
     * Swaps this device's records for the given rows, all or nothing. Rows
     * from an older version of the app lack the newer columns, which then
     * take their defaults.
     *
     * @param  array<string, list<array<string, mixed>>>  $tables
     */
    public function replace(array $tables): void
    {
        DB::transaction(function () use ($tables) {
            $this->clear();

            foreach (self::TABLES as $table) {
                $columns = array_flip(Schema::getColumnListing($table));

                foreach (array_chunk($tables[$table] ?? [], 200) as $chunk) {
                    DB::table($table)->insert(array_map(fn (array $row) => array_intersect_key($row, $columns), $chunk));
                }
            }
        });
    }

    /**
     * Removes every record, document, notification and device setting.
     */
    public function erase(): void
    {
        DB::transaction(function () {
            $this->clear();
            DB::table('app_settings')->delete();
            DB::table('notices')->delete();
        });

        Storage::disk('local')->deleteDirectory(self::DOCUMENTS);
        Storage::disk('local')->deleteDirectory('restore');
    }

    private function clear(): void
    {
        foreach (array_reverse(self::TABLES) as $table) {
            DB::table($table)->delete();
        }
    }

    private function key(string $table): string
    {
        return Schema::hasColumn($table, 'id') ? 'id' : Schema::getColumnListing($table)[0];
    }
}
