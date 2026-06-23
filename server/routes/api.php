<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\LookupController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PushTokenController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\TimeLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Native Mobile App API
|--------------------------------------------------------------------------
| Token-authenticated endpoints for the Expo client (Foreman / Field /
| Estimator experiences).
*/

// Public — authentication.
Route::post('/login', [AuthController::class, 'login']);
Route::post('/dev/login', [AuthController::class, 'devLogin']);

// Authenticated — Sanctum bearer token.
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Profile photo.
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar']);
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar']);

    // Jobs.
    Route::get('/jobs', [JobController::class, 'index']);
    Route::get('/jobs/{job}', [JobController::class, 'show']);
    Route::get('/jobs/{job}/map', [JobController::class, 'map']);
    Route::post('/jobs/{job}/start', [JobController::class, 'start']);
    Route::post('/jobs/{job}/complete', [JobController::class, 'complete']);
    Route::post('/jobs/{job}/notes', [JobController::class, 'addNote']);
    Route::post('/jobs/{job}/services/{jobService}/toggle', [JobController::class, 'toggleService']);
    Route::post('/jobs/{job}/media', [JobController::class, 'storeMedia']);
    Route::delete('/jobs/{job}/media/{media}', [JobController::class, 'destroyMedia']);

    // Foreman schedule (route-ordered day view).
    Route::get('/schedule', [ScheduleController::class, 'index']);

    // GPS breadcrumbs from the native app.
    Route::post('/locations', [LocationController::class, 'store']);

    // Foreman <-> office chat.
    Route::get('/chat', [ChatController::class, 'index']);
    Route::post('/chat', [ChatController::class, 'store']);
    Route::get('/chat/unread', [ChatController::class, 'unread']);

    // Push notification device tokens.
    Route::post('/push-token', [PushTokenController::class, 'store']);
    Route::delete('/push-token', [PushTokenController::class, 'destroy']);

    // Day-level time clock.
    Route::get('/time-logs', [TimeLogController::class, 'index']);
    Route::post('/time-logs/clock-in', [TimeLogController::class, 'clockIn']);
    Route::post('/time-logs/clock-out', [TimeLogController::class, 'clockOut']);

    // Estimator quotes.
    Route::get('/quotes', [QuoteController::class, 'index']);
    Route::post('/quotes', [QuoteController::class, 'store']);
    Route::get('/quotes/{quote}', [QuoteController::class, 'show']);
    Route::match(['put', 'patch'], '/quotes/{quote}', [QuoteController::class, 'update']);

    // Lookups for quote building.
    Route::get('/customers', [LookupController::class, 'customers']);
    Route::get('/customers/{customer}/properties', [LookupController::class, 'properties']);
    Route::get('/services', [LookupController::class, 'services']);
});
