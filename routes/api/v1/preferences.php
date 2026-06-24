<?php

use App\Http\Controllers\V1\UserPreferenceController;
use Illuminate\Support\Facades\Route;

Route::controller(UserPreferenceController::class)
    ->prefix('v1/preferences')
    ->middleware('auth:api')
    ->group(function () {
        Route::get('/', 'show');
        Route::put('/', 'update');
        Route::get('/feed', 'feed');
    });
