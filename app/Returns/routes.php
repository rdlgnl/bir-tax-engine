<?php

use App\Returns\Controllers\FileReturnController;
use App\Returns\Controllers\FilingController;
use App\Returns\Controllers\HistoryController;
use App\Returns\Controllers\ReportController;
use App\Returns\Controllers\SawtController;
use App\Returns\Controllers\SlspController;
use Illuminate\Support\Facades\Route;

Route::middleware('setup.complete')->group(function () {
    Route::get('/file', [FileReturnController::class, 'index'])->name('returns.index');
    Route::get('/file/{year}/eafs', [FileReturnController::class, 'eafs'])->whereNumber('year')->name('returns.eafs');

    Route::prefix('/file/{year}/{quarter}/{form}')
        ->whereNumber('year')
        ->where(['quarter' => '[1-4]', 'form' => '1701Q|1701-MS|1701A|1701|2551Q|2550Q'])
        ->group(function () {
            Route::get('/', [FileReturnController::class, 'show'])->name('returns.show');
            Route::get('/form', [FileReturnController::class, 'preview'])->name('returns.preview');
            Route::get('/pdf', [FileReturnController::class, 'pdf'])->name('returns.pdf');
            Route::get('/late', [FileReturnController::class, 'late'])->name('returns.late');
        });

    Route::prefix('/file/{year}/{quarter}/slsp')
        ->whereNumber('year')
        ->where(['quarter' => '[1-4]'])
        ->group(function () {
            Route::get('/', [SlspController::class, 'show'])->name('returns.slsp');
            Route::get('/{kind}', [SlspController::class, 'export'])->where('kind', 'sales|purchases|csv')->name('returns.slsp.export');
        });

    Route::prefix('/file/{year}/{quarter}/sawt')
        ->whereNumber('year')
        ->where(['quarter' => '[1-4]'])
        ->group(function () {
            Route::get('/', [SawtController::class, 'show'])->name('returns.sawt');
            Route::get('/export', [SawtController::class, 'export'])->name('returns.sawt.export');
        });

    Route::get('/history', HistoryController::class)->name('history');

    Route::prefix('/reports/{year}')->whereNumber('year')->name('reports.')->group(function () {
        Route::get('/tax-summary', [ReportController::class, 'summary'])->name('summary');
        Route::get('/income', [ReportController::class, 'income'])->name('income');
        Route::get('/sawt/{quarter}', [ReportController::class, 'sawt'])->where('quarter', '[1-4]')->name('sawt');
        Route::post('/package', [ReportController::class, 'package'])->name('package');
    });

    Route::post('/filings', [FilingController::class, 'store'])->name('filings.store');
    Route::patch('/filings/{filing}', [FilingController::class, 'update'])->name('filings.update');
});
