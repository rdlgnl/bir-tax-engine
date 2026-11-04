<?php

use App\Http\Controllers\ExternalLinkController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/home', HomeController::class)->middleware('setup.complete')->name('home');
Route::post('/external', ExternalLinkController::class)->name('external');
