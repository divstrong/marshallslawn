<?php

use App\Http\Controllers\PublicEstimateController;
use App\Http\Controllers\PublicInvoiceController;
use App\Http\Controllers\SmsOptInController;
use App\Http\Controllers\TwilioWebhookController;
use App\Livewire\DispatchBoard;
use Illuminate\Support\Facades\Route;

// The old mock native web app (/mobile) is retired — employees use the native
// Expo app and customers use the /portal Filament panel.

// Full-screen dispatch board (auth-protected, lives outside the Filament admin chrome).
Route::middleware(['auth'])->get('/dispatch', DispatchBoard::class)->name('dispatch.board');

Route::get('/estimate/{token}', [PublicEstimateController::class, 'show'])->name('estimate.public');
Route::post('/estimate/{token}/accept', [PublicEstimateController::class, 'accept'])->name('estimate.accept');
Route::post('/estimate/{token}/decline', [PublicEstimateController::class, 'decline'])->name('estimate.decline');

Route::get('/invoice/{token}', [PublicInvoiceController::class, 'show'])->name('invoice.public');
Route::post('/invoice/{token}/pay', [PublicInvoiceController::class, 'pay'])->name('invoice.pay');

// Public SMS opt-in (A2P 10DLC Call-to-Action page) + Twilio webhooks. The
// webhooks are CSRF-exempt (see bootstrap/app.php) and verified by Twilio
// signature instead.
Route::get('/sms-opt-in', [SmsOptInController::class, 'show'])->name('sms-opt-in');
Route::post('/sms-opt-in', [SmsOptInController::class, 'store'])->middleware('throttle:10,1')->name('sms-opt-in.store');

Route::post('/webhooks/twilio/inbound', [TwilioWebhookController::class, 'inbound'])->name('webhooks.twilio.inbound');
Route::post('/webhooks/twilio/status', [TwilioWebhookController::class, 'status'])->name('webhooks.twilio.status');
