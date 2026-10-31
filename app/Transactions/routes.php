<?php

use App\Transactions\Controllers\TransactionCertificateController;
use App\Transactions\Controllers\TransactionController;
use App\Transactions\Controllers\TransactionPreviewController;
use App\Transactions\Controllers\TransactionSearchController;
use Illuminate\Support\Facades\Route;

Route::middleware('setup.complete')->group(function () {
    Route::post('/transactions/preview', TransactionPreviewController::class)->name('transactions.preview');
    Route::get('/transactions/search', [TransactionSearchController::class, 'show'])->name('transactions.search');
    Route::get('/transactions/export', [TransactionSearchController::class, 'export'])->name('transactions.export');
    Route::put('/transactions/{transaction}/certificate', TransactionCertificateController::class)->name('transactions.certificate');
    Route::resource('transactions', TransactionController::class)->only(['index', 'store', 'update', 'destroy']);
});
