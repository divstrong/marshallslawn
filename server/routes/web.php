<?php

use App\Http\Controllers\PublicEstimateController;
use App\Http\Controllers\PublicInvoiceController;
use App\Livewire\DispatchBoard;
use App\Livewire\Mobile\MobileApp;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/mobile', MobileApp::class)->name('mobile.app');

// Full-screen dispatch board (auth-protected, lives outside the Filament admin chrome).
Route::middleware(['auth'])->get('/dispatch', DispatchBoard::class)->name('dispatch.board');

Route::get('/estimate/{token}', [PublicEstimateController::class, 'show'])->name('estimate.public');
Route::post('/estimate/{token}/accept', [PublicEstimateController::class, 'accept'])->name('estimate.accept');
Route::post('/estimate/{token}/decline', [PublicEstimateController::class, 'decline'])->name('estimate.decline');

Route::get('/invoice/{token}', [PublicInvoiceController::class, 'show'])->name('invoice.public');
Route::post('/invoice/{token}/pay', [PublicInvoiceController::class, 'pay'])->name('invoice.pay');
