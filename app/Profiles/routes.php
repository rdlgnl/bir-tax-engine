<?php

use App\Profiles\Controllers\ClassificationController;
use App\Profiles\Controllers\JobController;
use App\Profiles\Controllers\ProfileController;
use App\Profiles\Controllers\RegistrationController;
use App\Profiles\Controllers\SetupController;
use App\Profiles\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)->name('welcome');

Route::post('/setup/complete', [SetupController::class, 'complete'])->name('setup.complete');
Route::get('/setup/{step?}', [SetupController::class, 'show'])->name('setup.show');
Route::put('/setup/{step}', [SetupController::class, 'update'])->name('setup.update');

Route::middleware('setup.complete')->group(function () {
    Route::get('/profile', ProfileController::class)->name('profile');
    Route::put('/profile/job', [JobController::class, 'update'])->name('profile.job');
    Route::put('/profile/classification', [ClassificationController::class, 'update'])->name('profile.classification');
    Route::get('/profile/registration', [RegistrationController::class, 'show'])->name('registration');
    Route::get('/profile/registration/1905', [RegistrationController::class, 'form'])->name('registration.form');
});
