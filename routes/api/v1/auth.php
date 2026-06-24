<?php

use App\Http\Controllers\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)
    ->prefix('v1/auth')
    ->group(function () {
        Route::post('login', 'login')->middleware('throttle:5,1');
        Route::post('register', 'register')->middleware('throttle:10,1');

        Route::middleware('auth:api')->group(function () {
            Route::get('profile', 'profile');
            Route::post('refresh', 'refresh');
            Route::post('logout', 'logout');
        });
    });
