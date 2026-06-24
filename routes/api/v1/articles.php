<?php

use App\Http\Controllers\V1\ArticleController;
use Illuminate\Support\Facades\Route;

Route::controller(ArticleController::class)
    ->prefix('v1/articles')
    ->middleware('auth:api')
    ->group(function () {
        Route::get('/', 'index');
        Route::get('/{slug}', 'show');
    });
