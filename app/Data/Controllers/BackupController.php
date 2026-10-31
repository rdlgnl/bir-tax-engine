<?php

namespace App\Data\Controllers;

use App\Data\Requests\RestoreBackupRequest;
use App\Data\Requests\StoreBackupRequest;
use App\Data\Requests\UploadBackupRequest;
use App\Data\Services\Backups;
use App\Data\Services\InvalidBackup;
use App\Data\Services\LocalData;
use App\Http\Controllers\Controller;
use App\Profiles\Models\TaxProfile;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * Backing up into the backup folder, and restoring a backup file. Restore
 * also works before setup, for moving to a new device.
 */
class BackupController extends Controller
{
    /** The backup being restored, between choosing the file and the password. */
    private const PENDING = 'restore/pending.bbk';

    /** The chosen file's own name, to show back. */
    private const PENDING_NAME = 'restore/pending.name';

    public function __construct(private Backups $backups) {}

    public function show(LocalData $data): Response
    {
        return Inertia::render('data/backup', [
            'size' => $this->backups->estimatedSize(),
            'summary' => $data->summary(),
            'folder' => DataController::display($this->backups->folder()),
            'lastBackup' => $this->backups->last(),
            'pending' => $this->pending(),
        ]);
    }

    /**
     * Restoring on its own, from the welcome screen of a new device.
     */
    public function restorePage(): Response
    {
        return Inertia::render('data/restore', [
            'pending' => $this->pending(),
        ]);
    }

    public function store(StoreBackupRequest $request): RedirectResponse
    {
        try {
            $backup = $this->backups->create($request->validated('password'), CarbonImmutable::now());
        } catch (RuntimeException $e) {
            return back()->withErrors(['folder' => $e->getMessage()]);
        }

        return Inertia::flash('toast', "Backup saved: {$backup['file']}.")->back();
    }

    public function upload(UploadBackupRequest $request): RedirectResponse
    {
        $file = $request->file('backup');
        Storage::disk('local')->putFileAs('restore', $file, 'pending.bbk');
        Storage::disk('local')->put(self::PENDING_NAME, basename($file->getClientOriginalName()));

        try {
            $this->backups->inspect($this->pendingPath());
        } catch (InvalidBackup $e) {
            Storage::disk('local')->delete([self::PENDING, self::PENDING_NAME]);

            return back()->withErrors(['backup' => $e->getMessage()]);
        }

        return back();
    }

    public function restore(RestoreBackupRequest $request): RedirectResponse
    {
        if (! Storage::disk('local')->exists(self::PENDING)) {
            return back()->withErrors(['backup' => 'Choose the backup file again.']);
        }

        try {
            $this->backups->restore($this->pendingPath(), $request->validated('password'));
        } catch (InvalidBackup $e) {
            return back()->withErrors(['password' => $e->getMessage()]);
        }

        Storage::disk('local')->delete([self::PENDING, self::PENDING_NAME]);

        Inertia::flash('toast', 'Backup restored. Your records are back on this device.');

        return redirect()->route(TaxProfile::current()?->isComplete() ? 'home' : 'welcome');
    }

    public function cancel(): RedirectResponse
    {
        Storage::disk('local')->delete([self::PENDING, self::PENDING_NAME]);

        return back();
    }

    /**
     * What the chosen backup holds, with the file's name.
     *
     * @return array<string, mixed>|null
     */
    private function pending(): ?array
    {
        if (! Storage::disk('local')->exists(self::PENDING)) {
            return null;
        }

        try {
            return [
                ...$this->backups->inspect($this->pendingPath()),
                'file' => (string) Storage::disk('local')->get(self::PENDING_NAME),
            ];
        } catch (InvalidBackup) {
            return null;
        }
    }

    private function pendingPath(): string
    {
        return Storage::disk('local')->path(self::PENDING);
    }
}
