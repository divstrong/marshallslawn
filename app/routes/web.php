<?php

use App\Http\Controllers\PublicEstimateController;
use App\Livewire\Mobile\MobileApp;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/mobile', MobileApp::class)->name('mobile.app');

Route::get('/estimate/{token}', [PublicEstimateController::class, 'show'])->name('estimate.public');
Route::post('/estimate/{token}/accept', [PublicEstimateController::class, 'accept'])->name('estimate.accept');
Route::post('/estimate/{token}/decline', [PublicEstimateController::class, 'decline'])->name('estimate.decline');
