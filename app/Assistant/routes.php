<?php

use App\Assistant\Controllers\AssistantController;
use App\Assistant\Controllers\AssistantSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('setup.complete')->group(function () {
    Route::get('/ask', [AssistantController::class, 'show'])->name('ask');
    Route::post('/ask', [AssistantController::class, 'ask'])->name('ask.store');
    Route::delete('/ask', [AssistantController::class, 'clear'])->name('ask.clear');
    Route::post('/ask/feedback', [AssistantController::class, 'feedback'])->name('ask.feedback');
    Route::post('/ask/suggestions', [AssistantController::class, 'suggestion'])->name('ask.suggestion');
    Route::put('/data/assistant', [AssistantSettingsController::class, 'update'])->name('assistant.settings');
});
