<?php

use App\Books\Controllers\BooksController;
use App\Books\Controllers\LooseLeafController;
use Illuminate\Support\Facades\Route;

Route::middleware('setup.complete')->prefix('/books')->name('books.')->group(function () {
    Route::put('/type', [BooksController::class, 'type'])->name('type');
    Route::get('/loose-leaf', [LooseLeafController::class, 'show'])->name('loose-leaf');
    Route::get('/loose-leaf/application', [LooseLeafController::class, 'application'])->name('loose-leaf.application');

    Route::prefix('/{year}/{month}')->whereNumber(['year', 'month'])->group(function () {
        Route::post('/copied', [BooksController::class, 'copied'])->name('copied');
        Route::get('/pdf', [BooksController::class, 'pdf'])->name('pdf');
        Route::get('/general', [BooksController::class, 'general'])->name('general');
        Route::get('/general/pdf', [BooksController::class, 'generalPdf'])->name('general.pdf');
    });

    Route::get('/{year?}/{month?}', [BooksController::class, 'show'])->whereNumber(['year', 'month'])->name('show');
});
