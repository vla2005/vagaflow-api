<?php

use App\Http\Controllers\Api\AutomationProfileController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\ResumeController;
use App\Http\Controllers\Api\SessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/session', [SessionController::class, 'show']);
    Route::post('/session', [SessionController::class, 'store'])->middleware('throttle:5,1');
    Route::post('/register', [SessionController::class, 'register'])->middleware('throttle:3,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::delete('/session', [SessionController::class, 'destroy']);
        Route::get('/automation', [AutomationProfileController::class, 'show']);
        Route::put('/automation', [AutomationProfileController::class, 'update']);
        Route::post('/automation/resume', [ResumeController::class, 'store'])->middleware('throttle:10,1');
        Route::delete('/automation/resume', [ResumeController::class, 'destroy']);
        Route::get('/automation/resume/profile', [ResumeController::class, 'showProfile']);
        Route::put('/automation/resume/profile', [ResumeController::class, 'updateProfile']);
        Route::post('/automation/resume/retry', [ResumeController::class, 'retryParsing'])->middleware('throttle:5,1');
        Route::get('/jobs', [JobController::class, 'index']);
        Route::get('/jobs/{job}', [JobController::class, 'show']);
        Route::get('/jobs/{job}/resume', [JobController::class, 'downloadResume']);
        Route::patch('/jobs/{job}/status', [JobController::class, 'updateStatus']);
        Route::get('/push-subscriptions', [PushSubscriptionController::class, 'show']);
        Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->middleware('throttle:10,1');
        Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy']);
    });
});
