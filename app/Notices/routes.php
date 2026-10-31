<?php

use App\Notices\Controllers\NoticeController;
use Illuminate\Support\Facades\Route;

Route::middleware('setup.complete')->group(function () {
    Route::get('/notifications', [NoticeController::class, 'index'])->name('notifications');
    Route::post('/notifications/read', [NoticeController::class, 'read'])->name('notifications.read');
});
