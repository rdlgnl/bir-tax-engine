<?php

use App\Returns\Controllers\FileReturnController;
use App\Returns\Controllers\FilingController;
use App\Returns\Controllers\HistoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('setup.complete')->group(function () {
    Route::get('/file', [FileReturnController::class, 'index'])->name('returns.index');

    Route::prefix('/file/{year}/{quarter}/{form}')
        ->whereNumber('year')
        ->where(['quarter' => '[1-4]', 'form' => '1701Q|1701A|1701|2551Q'])
        ->group(function () {
            Route::get('/', [FileReturnController::class, 'show'])->name('returns.show');
            Route::get('/form', [FileReturnController::class, 'preview'])->name('returns.preview');
            Route::get('/pdf', [FileReturnController::class, 'pdf'])->name('returns.pdf');
        });

    Route::get('/history', HistoryController::class)->name('history');

    Route::post('/filings', [FilingController::class, 'store'])->name('filings.store');
    Route::patch('/filings/{filing}', [FilingController::class, 'update'])->name('filings.update');
});
