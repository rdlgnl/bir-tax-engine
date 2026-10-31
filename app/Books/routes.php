<?php

use App\Books\Controllers\BooksController;
use Illuminate\Support\Facades\Route;

Route::middleware('setup.complete')->prefix('/books')->name('books.')->group(function () {
    Route::put('/type', [BooksController::class, 'type'])->name('type');

    Route::prefix('/{year}/{month}')->whereNumber(['year', 'month'])->group(function () {
        Route::post('/copied', [BooksController::class, 'copied'])->name('copied');
        Route::get('/pdf', [BooksController::class, 'pdf'])->name('pdf');
    });

    Route::get('/{year?}/{month?}', [BooksController::class, 'show'])->whereNumber(['year', 'month'])->name('show');
});
