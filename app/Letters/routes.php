<?php

use App\Letters\Controllers\LetterController;
use Illuminate\Support\Facades\Route;

Route::middleware('setup.complete')->group(function () {
    Route::get('/letters', [LetterController::class, 'index'])->name('letters.index');
    Route::get('/letters/new', [LetterController::class, 'create'])->name('letters.create');
    Route::post('/letters', [LetterController::class, 'store'])->name('letters.store');
    Route::get('/letters/{letter}', [LetterController::class, 'show'])->whereNumber('letter')->name('letters.show');
    Route::post('/letters/{letter}/resolve', [LetterController::class, 'resolve'])->whereNumber('letter')->name('letters.resolve');
    Route::delete('/letters/{letter}', [LetterController::class, 'destroy'])->whereNumber('letter')->name('letters.destroy');
    Route::get('/letters/{letter}/file', [LetterController::class, 'file'])->whereNumber('letter')->name('letters.file');
});
