<?php

use App\Data\Controllers\BackupController;
use App\Data\Controllers\DataController;
use App\Data\Controllers\EraseController;
use App\Data\Controllers\FirstRunController;
use App\Data\Controllers\LockController;
use Illuminate\Support\Facades\Route;

// The lock screen is the one place the lock middleware lets through.
Route::get('/lock', [LockController::class, 'show'])->name('lock');
Route::post('/lock', [LockController::class, 'unlock'])->name('lock.unlock');

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
    Route::put('/lock', [LockController::class, 'update'])->name('lock.settings');
    Route::delete('/lock', [LockController::class, 'destroy'])->name('lock.disable');
    Route::get('/erase', [EraseController::class, 'show'])->name('erase');
    Route::delete('/', [EraseController::class, 'destroy'])->name('erase.destroy');
});

Route::middleware('setup.complete')->group(function () {
    Route::post('/lock/now', [LockController::class, 'lockNow'])->name('lock.now');
    Route::get('/first-run', [FirstRunController::class, 'show'])->name('first-run');
    Route::post('/first-run', [FirstRunController::class, 'store'])->name('first-run.store');
    Route::post('/first-run/skip', [FirstRunController::class, 'skip'])->name('first-run.skip');
});
