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
    Route::get('/{year}/{month}', [PayrollController::class, 'month'])->whereNumber(['year', 'month'])->name('payroll.month');
    Route::get('/{year}/{month}/pdf', [PayrollController::class, 'monthPdf'])->whereNumber(['year', 'month'])->name('payroll.month.pdf');
});
