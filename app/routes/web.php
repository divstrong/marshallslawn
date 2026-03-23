<?php

use App\Livewire\Mobile\MobileApp;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/mobile', MobileApp::class)->name('mobile.app');
