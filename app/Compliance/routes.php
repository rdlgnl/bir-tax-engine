<?php

use App\Compliance\Controllers\CalendarController;
use App\Compliance\Controllers\CertificateController;
use App\Compliance\Controllers\CompensationController;
use App\Compliance\Controllers\ComplianceController;
use App\Compliance\Controllers\SwornDeclarationController;
use Illuminate\Support\Facades\Route;

Route::middleware('setup.complete')->group(function () {
    Route::get('/compliance/{quarter?}', ComplianceController::class)->where('quarter', '[1-4]')->name('compliance.show');

    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
    Route::get('/calendar/export', [CalendarController::class, 'export'])->name('calendar.export');

    Route::get('/sworn/{year?}', [SwornDeclarationController::class, 'show'])->whereNumber('year')->name('sworn.show');
    Route::get('/sworn/{year}/pdf/{client?}', [SwornDeclarationController::class, 'pdf'])->whereNumber('year')->where('client', '[a-z0-9-]+')->name('sworn.pdf');

    Route::get('/certificates/create', [CertificateController::class, 'create'])->name('certificates.create');
    Route::post('/certificates', [CertificateController::class, 'store'])->name('certificates.store');
    Route::post('/certificates/match', [CertificateController::class, 'match'])->name('certificates.match');
    Route::post('/certificates/read', [CertificateController::class, 'read'])->name('certificates.read');
    Route::get('/certificates/{certificate}/file', [CertificateController::class, 'file'])->name('certificates.file');

    Route::get('/2316/{year?}', [CompensationController::class, 'show'])->whereNumber('year')->name('compensation');
    Route::post('/2316/{year}', [CompensationController::class, 'store'])->whereNumber('year')->name('compensation.store');
    Route::get('/2316/{year}/file', [CompensationController::class, 'file'])->whereNumber('year')->name('compensation.file');
});
