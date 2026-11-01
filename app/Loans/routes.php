<?php

use App\Loans\Controllers\LoanController;
use Illuminate\Support\Facades\Route;

Route::middleware('setup.complete')->group(function () {
    Route::get('/loans', [LoanController::class, 'index'])->name('loans.index');
    Route::get('/loans/new', [LoanController::class, 'create'])->name('loans.create');
    Route::post('/loans', [LoanController::class, 'store'])->name('loans.store');
    Route::get('/loans/{loan}', [LoanController::class, 'show'])->whereNumber('loan')->name('loans.show');
    Route::put('/loans/{loan}', [LoanController::class, 'update'])->whereNumber('loan')->name('loans.update');
    Route::delete('/loans/{loan}', [LoanController::class, 'destroy'])->whereNumber('loan')->name('loans.destroy');
    Route::get('/loans/{loan}/form', [LoanController::class, 'form'])->whereNumber('loan')->name('loans.form');
    Route::get('/loans/{loan}/pdf', [LoanController::class, 'pdf'])->whereNumber('loan')->name('loans.pdf');
    Route::post('/loans/{loan}/filed', [LoanController::class, 'filed'])->whereNumber('loan')->name('loans.filed');
});
