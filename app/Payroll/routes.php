<?php

use App\Payroll\Controllers\PayrollController;
use Illuminate\Support\Facades\Route;

Route::middleware('setup.complete')->prefix('/payroll')->group(function () {
    Route::get('/{year?}', [PayrollController::class, 'index'])->whereNumber('year')->name('payroll');
    Route::get('/employees/new', [PayrollController::class, 'create'])->name('payroll.employees.create');
    Route::post('/employees', [PayrollController::class, 'store'])->name('payroll.employees.store');
    Route::get('/employees/{employee}', [PayrollController::class, 'show'])->whereNumber('employee')->name('payroll.employee');
    Route::put('/employees/{employee}', [PayrollController::class, 'update'])->whereNumber('employee')->name('payroll.employees.update');
    Route::delete('/employees/{employee}', [PayrollController::class, 'destroy'])->whereNumber('employee')->name('payroll.employees.destroy');
    Route::get('/employees/{employee}/2316/{year?}', [PayrollController::class, 'certificate'])->whereNumber(['employee', 'year'])->name('payroll.certificate');
    Route::get('/employees/{employee}/2316/{year}/pdf', [PayrollController::class, 'certificatePdf'])->whereNumber(['employee', 'year'])->name('payroll.certificate.pdf');
    Route::post('/employees/{employee}/2316/{year}/given', [PayrollController::class, 'given'])->whereNumber(['employee', 'year'])->name('payroll.certificate.given');
    Route::get('/{year}/annual', [PayrollController::class, 'annual'])->whereNumber('year')->name('payroll.annual');
    Route::get('/{year}/annual/pdf', [PayrollController::class, 'annualPdf'])->whereNumber('year')->name('payroll.annual.pdf');
    Route::get('/{year}/{month}', [PayrollController::class, 'month'])->whereNumber(['year', 'month'])->name('payroll.month');
    Route::get('/{year}/{month}/pdf', [PayrollController::class, 'monthPdf'])->whereNumber(['year', 'month'])->name('payroll.month.pdf');
});
