<?php

namespace App\Transactions\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use SplFileObject;
use Throwable;

/**
 * Reading a CSV export on this computer: its records whatever the
 * delimiter, and the dates and peso amounts written the ways banks and
 * platforms write them.
 */
class CsvFile
{
    private const DATE_FORMATS = ['Y-m-d', 'm/d/Y', 'm/d/y', 'n/j/Y', 'Y/m/d', 'd-M-Y', 'd-M-y', 'd M Y', 'M d, Y', 'M j, Y', 'F j, Y', 'd/m/Y', 'Y-m-d H:i:s', 'm/d/Y H:i', 'm/d/Y h:i A'];

    /**
     * Every record of the file, with the delimiter it uses.
     *
     * @return list<list<string|null>>
     */
    public function records(string $path): array
    {
        $file = new SplFileObject($path);
        // The delimiter is the one the opening lines use most; some start with a title line.
        $opening = '';

        for ($i = 0; $i < 10 && ! $file->eof(); $i++) {
            $opening .= (string) $file->fgets();
        }

        $delimiter = collect([',', ';', "\t", '|'])->sortByDesc(fn ($d) => substr_count($opening, $d))->first();
        $file->rewind();
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD);
        $file->setCsvControl($delimiter, '"', '');
        $records = [];

        foreach ($file as $record) {
            if (is_array($record)) {
                // Drop a byte-order mark from the first cell.
                $records[] = array_map(fn ($v) => $v === null ? null : preg_replace('/^\x{FEFF}/u', '', $v), array_values($record));
            }
        }

        return $records;
    }

    public function date(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        foreach (self::DATE_FORMATS as $format) {
            try {
                $date = CarbonImmutable::createFromFormat('!'.$format, $value);

                if ($date !== null && $date->format($format) === $value) {
                    return $date->toDateString();
                }
            } catch (Throwable) {
                // Try the next format.
            }
        }

        try {
            return CarbonImmutable::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * "₱1,234.50", "(1,234.50)", "1,234.50 DR" and "-1234.5" as numbers.
     */
    public function money(?string $value): ?float
    {
        $value = Str::upper(trim((string) $value));

        if ($value === '') {
            return null;
        }

        $negative = str_starts_with($value, '-') || str_starts_with($value, '(') || str_ends_with($value, 'DR');
        $digits = preg_replace('/[^\d.]/', '', $value);

        if ($digits === null || $digits === '' || ! is_numeric($digits)) {
            return null;
        }

        return ($negative ? -1 : 1) * round((float) $digits, 2);
    }
}
