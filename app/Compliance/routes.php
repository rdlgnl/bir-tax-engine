<?php

use App\Compliance\Controllers\CalendarController;
use App\Compliance\Controllers\CertificateController;
use App\Compliance\Controllers\ComplianceController;
use Illuminate\Support\Facades\Route;

Route::middleware('setup.complete')->group(function () {
    Route::get('/compliance/{quarter?}', ComplianceController::class)->where('quarter', '[1-4]')->name('compliance.show');

    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
    Route::get('/calendar/export', [CalendarController::class, 'export'])->name('calendar.export');

    Route::get('/certificates/create', [CertificateController::class, 'create'])->name('certificates.create');
    Route::post('/certificates', [CertificateController::class, 'store'])->name('certificates.store');
    Route::post('/certificates/match', [CertificateController::class, 'match'])->name('certificates.match');
    Route::get('/certificates/{certificate}/file', [CertificateController::class, 'file'])->name('certificates.file');
});
