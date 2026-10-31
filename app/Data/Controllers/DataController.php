<?php

namespace App\Data\Controllers;

use App\Data\Models\Setting;
use App\Data\Requests\UpdateDataSettingsRequest;
use App\Data\Services\Backups;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Your data": where the records live, backups and their folder, and the
 * rule pack in use.
 */
class DataController extends Controller
{
    public function show(Backups $backups): Response
    {
        $today = CarbonImmutable::now();
        $last = $backups->last();

        return Inertia::render('data/show', [
            'size' => $backups->estimatedSize(),
            'folder' => $this->display($backups->folder()),
            'lastBackup' => $last ? [...$last, 'days_since' => (int) CarbonImmutable::parse($last['at'])->diffInDays($today)] : null,
            'remindMonthly' => $backups->remindMonthly(),
            'rulePack' => ['version' => config('tax.version'), 'reviewed' => config('tax.reviewed')],
        ]);
    }

    public function update(UpdateDataSettingsRequest $request): RedirectResponse
    {
        if ($request->has('backup_folder')) {
            Setting::put('backup_folder', $request->folder());
        }

        if ($request->has('backup_reminder')) {
            Setting::put('backup_reminder', $request->boolean('backup_reminder'));
        }

        return Inertia::flash('toast', 'Saved.')->back();
    }

    /**
     * The folder with the home folder shown as ~, as people write it.
     */
    public static function display(string $folder): string
    {
        $home = rtrim((string) ($_SERVER['HOME'] ?? getenv('HOME')), '/');

        return $home !== '' && str_starts_with($folder, $home.'/') ? '~'.substr($folder, strlen($home)) : $folder;
    }
}
