<?php

use App\Data\Controllers\BackupController;
use App\Data\Controllers\DataController;
use App\Data\Controllers\EraseController;
use Illuminate\Support\Facades\Route;

// Restoring works before setup too: that's how records reach a new device.
Route::prefix('/data/restore')->name('restore.')->group(function () {
    Route::get('/', [BackupController::class, 'restorePage'])->name('show');
    Route::post('/', [BackupController::class, 'upload'])->name('upload');
    Route::post('/confirm', [BackupController::class, 'restore'])->name('confirm');
    Route::delete('/', [BackupController::class, 'cancel'])->name('cancel');
});

Route::middleware('setup.complete')->prefix('/data')->group(function () {
    Route::get('/', [DataController::class, 'show'])->name('data');
    Route::put('/', [DataController::class, 'update'])->name('data.update');
    Route::get('/backup', [BackupController::class, 'show'])->name('backup');
    Route::post('/backup', [BackupController::class, 'store'])->name('backup.store');
    Route::get('/erase', [EraseController::class, 'show'])->name('erase');
    Route::delete('/', [EraseController::class, 'destroy'])->name('erase.destroy');
});
