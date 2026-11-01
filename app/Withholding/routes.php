<?php

use App\Withholding\Controllers\WithholdingController;
use Illuminate\Support\Facades\Route;

Route::middleware('setup.complete')->prefix('/withholding')->group(function () {
    Route::get('/{year?}', [WithholdingController::class, 'index'])->whereNumber('year')->name('withholding');

    Route::prefix('/{year}/annual')->whereNumber('year')->group(function () {
        Route::get('/', [WithholdingController::class, 'annual'])->name('withholding.annual');
        Route::get('/form', [WithholdingController::class, 'annualPreview'])->name('withholding.annual.form');
        Route::get('/pdf', [WithholdingController::class, 'annualPdf'])->name('withholding.annual.pdf');
        Route::get('/alphalist/{kind}', [WithholdingController::class, 'alphalist'])->where('kind', 'dat|csv')->name('withholding.alphalist');
    });

    Route::prefix('/{year}/month/{month}')->whereNumber(['year', 'month'])->name('withholding.month')->group(function () {
        Route::get('/', [WithholdingController::class, 'month']);
        Route::get('/pdf', [WithholdingController::class, 'monthPdf'])->name('.pdf');
    });

    Route::prefix('/{year}/quarter/{quarter}')->whereNumber('year')->where(['quarter' => '[1-4]'])->group(function () {
        Route::get('/', [WithholdingController::class, 'quarter'])->name('withholding.quarter');
        Route::get('/form', [WithholdingController::class, 'quarterPreview'])->name('withholding.quarter.form');
        Route::get('/pdf', [WithholdingController::class, 'quarterPdf'])->name('withholding.quarter.pdf');
        Route::get('/qap/{kind}', [WithholdingController::class, 'qap'])->where('kind', 'dat|csv')->name('withholding.qap');
        Route::get('/2307/{payee}', [WithholdingController::class, 'certificate'])->where('payee', '[a-z0-9-]+')->name('withholding.certificate');
        Route::get('/2307/{payee}/pdf', [WithholdingController::class, 'certificatePdf'])->where('payee', '[a-z0-9-]+')->name('withholding.certificate.pdf');
        Route::post('/2307/{payee}/given', [WithholdingController::class, 'given'])->where('payee', '[a-z0-9-]+')->name('withholding.certificate.given');
    });
});
