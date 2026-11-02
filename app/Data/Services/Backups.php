<?php

namespace App\Data\Services;

use App\Data\Models\RecordChange;
use App\Data\Models\Setting;
use App\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Backup files (.bbk): a ZIP whose records and documents are encrypted with
 * a password the taxpayer chooses and nobody else knows. A small manifest
 * stays readable without it, so a restore can say what a file holds before
 * asking for the password. It names no one: a date, a device type, counts.
 *
 * @phpstan-import-type Summary from LocalData
 *
 * @phpstan-type Manifest array{app: string, format: int, made_at: string, device: string, schema: string|null, rule_pack: string, summary: Summary}
 * @phpstan-type LastBackup array{at: string, file: string, folder: string, size: int}
 */
class Backups
{
    public const FORMAT = 1;

    public function __construct(private LocalData $data) {}

    public function folder(): string
    {
        return (string) Setting::get('backup_folder', config('backup.folder'));
    }

    /**
     * @return LastBackup|null
     */
    public function last(): ?array
    {
        return Setting::get('last_backup');
    }

    public function remindMonthly(): bool
    {
        return (bool) Setting::get('backup_reminder', true);
    }

    /**
     * Days since the last backup when one is due, or null when it isn't.
     * Without any backup yet, a reminder is due once there are records.
     *
     * @return array{days_since: int|null}|null
     */
    public function reminder(CarbonImmutable $today): ?array
    {
        if (! $this->remindMonthly() || ! Transaction::query()->exists()) {
            return null;
        }

        $last = $this->last();
        $days = $last ? (int) CarbonImmutable::parse($last['at'])->diffInDays($today) : null;

        return $days === null || $days >= config('backup.reminder_days') ? ['days_since' => $days] : null;
    }

    /**
     * About how big a backup would be, before compression.
     */
    public function estimatedSize(): int
    {
        $disk = Storage::disk('local');

        return strlen((string) json_encode($this->data->rows()))
            + array_sum(array_map(fn (string $path) => $disk->size($path), $this->data->documents()));
    }

    /**
     * Writes a backup into the backup folder and remembers it.
     *
     * @return LastBackup
     */
    public function create(string $password, CarbonImmutable $now): array
    {
        $folder = $this->folder();

        if (! is_dir($folder) && ! @mkdir($folder, 0755, true) && ! is_dir($folder)) {
            throw new RuntimeException("Can't create the backup folder {$folder}.");
        }

        $name = $this->freeName($folder, $now);
        $path = "{$folder}/{$name}";
        // Written beside the final file and renamed, so a failed backup leaves no half file.
        $partial = "{$path}.part";

        $zip = new ZipArchive;

        if ($zip->open($partial, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Can't write a backup into {$folder}.");
        }

        $zip->addFromString('manifest.json', (string) json_encode($this->manifest($now), JSON_PRETTY_PRINT));

        $secret = function (string $entry, string $contents) use ($zip, $password) {
            $zip->addFromString($entry, $contents);
            $zip->setEncryptionName($entry, ZipArchive::EM_AES_256, $password);
        };

        $secret('data.json', (string) json_encode(['tables' => $this->data->rows()]));

        foreach ($this->data->documents() as $document) {
            $secret("documents/{$document}", (string) Storage::disk('local')->get($document));
        }

        $zip->close();
        rename($partial, $path);

        $backup = ['at' => $now->toIso8601String(), 'file' => $name, 'folder' => $folder, 'size' => (int) filesize($path)];
        Setting::put('last_backup', $backup);

        return $backup;
    }

    /**
     * What a backup file says it holds. Needs no password.
     *
     * @return Manifest
     */
    public function inspect(string $path): array
    {
        $zip = $this->open($path);
        $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
        $zip->close();

        if (! is_array($manifest) || ($manifest['app'] ?? null) !== 'BuwisBuddy') {
            throw new InvalidBackup('This isn’t a BuwisBuddy backup.');
        }

        $schema = is_string($manifest['schema'] ?? null) ? $manifest['schema'] : null;

        if (($manifest['format'] ?? null) !== self::FORMAT || $this->newerThanThisApp($schema)) {
            throw new InvalidBackup('This backup was made by a newer BuwisBuddy. Update the app, then restore it.');
        }

        // The file came from outside the app: keep only the fields shown, as their types.
        $summary = is_array($manifest['summary'] ?? null) ? $manifest['summary'] : [];
        $count = fn (string $key) => is_int($summary[$key] ?? null) ? $summary[$key] : 0;
        $year = fn (string $key) => is_int($summary[$key] ?? null) ? $summary[$key] : null;

        return [
            'app' => 'BuwisBuddy',
            'format' => self::FORMAT,
            'made_at' => is_string($manifest['made_at'] ?? null) ? $manifest['made_at'] : '',
            'device' => is_string($manifest['device'] ?? null) ? $manifest['device'] : 'Computer',
            'schema' => $schema,
            'rule_pack' => is_string($manifest['rule_pack'] ?? null) ? $manifest['rule_pack'] : '',
            'summary' => [
                'records' => $count('records'),
                'first_year' => $year('first_year'),
                'last_year' => $year('last_year'),
                'certificates' => $count('certificates'),
                'documents' => $count('documents'),
                'filings' => $count('filings'),
            ],
        ];
    }

    /**
     * Replaces this device's records and documents with the backup's.
     */
    public function restore(string $path, string $password): void
    {
        $made = $this->inspect($path)['made_at'];

        $zip = $this->open($path);
        $zip->setPassword($password);
        $data = $zip->getFromName('data.json');

        if ($data === false) {
            $zip->close();

            throw new InvalidBackup('That password doesn’t open this backup.');
        }

        $tables = json_decode($data, true);

        if (! is_array($tables) || ! is_array($tables['tables'] ?? null)) {
            $zip->close();

            throw new InvalidBackup('This backup is damaged and can’t be restored.');
        }

        $documents = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = (string) $zip->getNameIndex($i);

            // Only plain file names inside the documents folder; nothing that could climb out of it.
            if (preg_match('#^documents/('.LocalData::DOCUMENTS.'/[A-Za-z0-9._-]+)$#', $entry, $match)) {
                $documents[$match[1]] = $zip->getFromIndex($i);
            }
        }

        $zip->close();

        $this->data->replace($tables['tables']);
        // The backup brings its own history; the restore itself goes on top.
        RecordChange::query()->create([
            'subject' => 'backup',
            'event' => 'restored',
            'label' => 'Records restored from a backup'.($made !== '' ? ' made '.CarbonImmutable::parse($made)->format('M j, Y') : ''),
            'cause' => request()->route()?->getName(),
            'at' => CarbonImmutable::now(),
        ]);

        $disk = Storage::disk('local');
        $disk->deleteDirectory(LocalData::DOCUMENTS);

        foreach ($documents as $document => $contents) {
            if ($contents !== false) {
                $disk->put($document, $contents);
            }
        }
    }

    /**
     * @return Manifest
     */
    private function manifest(CarbonImmutable $now): array
    {
        return [
            'app' => 'BuwisBuddy',
            'format' => self::FORMAT,
            'made_at' => $now->toIso8601String(),
            'device' => match (PHP_OS_FAMILY) {
                'Darwin' => 'Mac',
                'Windows' => 'Windows computer',
                'Linux' => 'Linux computer',
                default => 'Computer',
            },
            'schema' => $this->schema(),
            'rule_pack' => (string) config('tax.version'),
            'summary' => $this->data->summary(),
        ];
    }

    /**
     * The latest migration, which names the shape of the tables.
     */
    private function schema(): ?string
    {
        $latest = DB::table('migrations')->max('migration');

        return is_string($latest) ? $latest : null;
    }

    private function newerThanThisApp(?string $schema): bool
    {
        return $schema !== null && $schema > (string) $this->schema();
    }

    private function open(string $path): ZipArchive
    {
        $zip = new ZipArchive;

        if (! is_file($path) || $zip->open($path, ZipArchive::RDONLY) !== true) {
            throw new InvalidBackup('This isn’t a BuwisBuddy backup.');
        }

        return $zip;
    }

    /**
     * buwisbuddy-2026-10-28.bbk, or -2, -3… when that day already has one.
     */
    private function freeName(string $folder, CarbonImmutable $now): string
    {
        $base = 'buwisbuddy-'.$now->toDateString();
        $extension = config('backup.extension');
        $name = "{$base}.{$extension}";

        for ($n = 2; file_exists("{$folder}/{$name}"); $n++) {
            $name = "{$base}-{$n}.{$extension}";
        }

        return $name;
    }
}
