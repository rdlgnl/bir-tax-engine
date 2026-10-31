<?php

namespace App\Data\Controllers;

use App\Data\Requests\EraseDataRequest;
use App\Data\Services\Backups;
use App\Data\Services\LocalData;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Erasing everything from this device. There's no copy anywhere else, so
 * the page says what goes and when the last backup was.
 */
class EraseController extends Controller
{
    public function show(LocalData $data, Backups $backups): Response
    {
        return Inertia::render('data/erase', [
            'summary' => $data->summary(),
            'lastBackup' => $backups->last(),
            'retentionYears' => config('tax.books.retention_years'),
        ]);
    }

    public function destroy(EraseDataRequest $request, LocalData $data): RedirectResponse
    {
        $data->erase();

        Inertia::flash('toast', 'Everything is erased from this device.');

        return redirect()->route('welcome');
    }
}
