<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Controllers\UserController;
use Modules\Authentication\Http\Controllers\UserDraftController;

// Routes for User (tanpa tenant karena di database central)
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Master Resource
    Route::prefix('authentication/user')->controller(UserController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::put('/{id}/revise', 'revise');
        Route::post('/{id}/reorder', 'reorder');
        Route::delete('/{id}', 'destroy');
    });

    // Draft Resource
    Route::prefix('authentication/user-drafts')->controller(UserDraftController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::post('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
        Route::post('/{id}/commit', 'commit');
    });
});
