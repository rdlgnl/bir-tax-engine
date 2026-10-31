<?php

use App\Tax\Controllers\CompareController;
use App\Tax\Controllers\RuleController;
use App\Tax\Controllers\TaxController;
use Illuminate\Support\Facades\Route;

Route::middleware('setup.complete')->group(function () {
    Route::get('/tax/compare', CompareController::class)->name('tax.compare');
    Route::get('/rules', [RuleController::class, 'show'])->name('rules');
    Route::post('/rules/seen', [RuleController::class, 'seen'])->name('rules.seen');
    Route::get('/tax/{quarter?}', [TaxController::class, 'show'])->where('quarter', '[1-4]')->name('tax.show');
});
