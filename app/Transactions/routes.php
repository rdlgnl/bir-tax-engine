<?php

use App\Transactions\Controllers\TransactionCertificateController;
use App\Transactions\Controllers\TransactionController;
use App\Transactions\Controllers\TransactionImportController;
use App\Transactions\Controllers\TransactionPreviewController;
use App\Transactions\Controllers\TransactionSearchController;
use Illuminate\Support\Facades\Route;

Route::middleware('setup.complete')->group(function () {
    Route::post('/transactions/preview', TransactionPreviewController::class)->name('transactions.preview');
    Route::get('/transactions/search', [TransactionSearchController::class, 'show'])->name('transactions.search');
    Route::get('/transactions/export', [TransactionSearchController::class, 'export'])->name('transactions.export');
    Route::get('/transactions/import', [TransactionImportController::class, 'show'])->name('transactions.import');
    Route::post('/transactions/import', [TransactionImportController::class, 'upload'])->name('transactions.import.upload');
    Route::put('/transactions/import/columns', [TransactionImportController::class, 'columns'])->name('transactions.import.columns');
    Route::post('/transactions/import/confirm', [TransactionImportController::class, 'confirm'])->name('transactions.import.confirm');
    Route::delete('/transactions/import', [TransactionImportController::class, 'cancel'])->name('transactions.import.cancel');
    Route::put('/transactions/{transaction}/certificate', TransactionCertificateController::class)->name('transactions.certificate');
    Route::resource('transactions', TransactionController::class)->only(['index', 'store', 'update', 'destroy']);
});
