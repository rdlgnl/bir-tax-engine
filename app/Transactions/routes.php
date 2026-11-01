<?php

use App\Transactions\Controllers\MarketplaceImportController;
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
    Route::get('/transactions/import/marketplace', [MarketplaceImportController::class, 'show'])->name('transactions.marketplace');
    Route::post('/transactions/import/marketplace', [MarketplaceImportController::class, 'upload'])->name('transactions.marketplace.upload');
    Route::put('/transactions/import/marketplace/columns', [MarketplaceImportController::class, 'columns'])->name('transactions.marketplace.columns');
    Route::post('/transactions/import/marketplace/confirm', [MarketplaceImportController::class, 'confirm'])->name('transactions.marketplace.confirm');
    Route::delete('/transactions/import/marketplace', [MarketplaceImportController::class, 'cancel'])->name('transactions.marketplace.cancel');
    Route::put('/transactions/{transaction}/certificate', TransactionCertificateController::class)->name('transactions.certificate');
    Route::resource('transactions', TransactionController::class)->only(['index', 'store', 'update', 'destroy']);
});
