<?php

use Modules\Core\Http\Controllers\DictionaryController;
use Modules\Core\Http\Controllers\DictionaryDraftController;

use Illuminate\Support\Facades\Route;

// Routes for Dictionary
Route::group(['middleware' => 'auth:sanctum', 'prefix' => 'v1'], function () {
    // Master Resource
    Route::prefix('dictionaries')->group(function () {
        Route::get('/', [DictionaryController::class, 'index']);
        Route::get('/{id}', [DictionaryController::class, 'show']);
        Route::post('/{id}/revise', [DictionaryController::class, 'revise']);
        Route::delete('/{id}', [DictionaryController::class, 'destroy']);
    });

    // Draft Resource
    Route::prefix('dictionary-drafts')->group(function () {
        Route::get('/', [DictionaryDraftController::class, 'index']);
        Route::post('/', [DictionaryDraftController::class, 'store']);
        Route::get('/{id}', [DictionaryDraftController::class, 'show']);
        Route::put('/{id}', [DictionaryDraftController::class, 'update']);
        Route::delete('/{id}', [DictionaryDraftController::class, 'destroy']);
        Route::post('/{id}/commit', [DictionaryDraftController::class, 'commit']);
    });
});
