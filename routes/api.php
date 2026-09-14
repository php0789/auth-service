<?php

use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\LoginConroller;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->group(function (): void {
    Route::post('/register', RegisterController::class)
        ->middleware('throttle:10,1')
        ->name('auth.register');
    
    Route::post('/login', LoginConroller::class)
        ->middleware('throttle:10,1')
        ->name('auth.login');
});
