<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Controllers\UserController;
use Modules\Authentication\Http\Controllers\UserDraftController;

// Routes for User (tanpa tenant karena di database central)
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Master Resource
    Route::prefix('authentication/user')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}/revise', [UserController::class, 'revise']);
        Route::post('/{id}/reorder', [UserController::class, 'reorder']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    // Draft Resource
    Route::prefix('authentication/user-drafts')->group(function () {
        Route::get('/', [UserDraftController::class, 'index']);
        Route::post('/', [UserDraftController::class, 'store']);
        Route::get('/{id}', [UserDraftController::class, 'show']);
        Route::put('/{id}', [UserDraftController::class, 'update']);
        Route::delete('/{id}', [UserDraftController::class, 'destroy']);
        Route::post('/{id}/commit', [UserDraftController::class, 'commit']);
    });
});
