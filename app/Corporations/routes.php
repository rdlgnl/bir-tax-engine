<?php

use App\Corporations\Controllers\CorporationController;
use Illuminate\Support\Facades\Route;

Route::middleware('setup.complete')->group(function () {
    Route::get('/corporation', [CorporationController::class, 'show'])->name('corporation.show');
    Route::get('/corporation/edit', [CorporationController::class, 'edit'])->name('corporation.edit');
    Route::put('/corporation', [CorporationController::class, 'save'])->name('corporation.save');
    Route::delete('/corporation', [CorporationController::class, 'destroy'])->name('corporation.destroy');

    Route::prefix('/corporation/{year}/{quarter}')
        ->whereNumber('year')
        ->where(['quarter' => '[1-3]'])
        ->group(function () {
            Route::get('/', [CorporationController::class, 'quarter'])->name('corporation.quarter');
            Route::put('/', [CorporationController::class, 'saveQuarter'])->name('corporation.quarter.save');
            Route::get('/form', [CorporationController::class, 'form'])->name('corporation.form');
            Route::get('/pdf', [CorporationController::class, 'pdf'])->name('corporation.pdf');
            Route::post('/filed', [CorporationController::class, 'filed'])->name('corporation.filed');
        });
});
