<?php

use App\Http\Controllers\CheckController;
use App\Http\Controllers\WebsiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WebsiteController::class, 'index'])->name('websites.index');
Route::resource('websites', WebsiteController::class)->except('index');
Route::post('/websites/{website}/check', CheckController::class)->middleware('throttle:manual-checks')->name('websites.check');
