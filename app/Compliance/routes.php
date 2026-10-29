<?php

use App\Compliance\Controllers\FilingController;
use Illuminate\Support\Facades\Route;

Route::middleware('setup.complete')->group(function () {
    Route::post('/filings', [FilingController::class, 'store'])->name('filings.store');
});
