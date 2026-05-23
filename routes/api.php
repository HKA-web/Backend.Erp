<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ApiDocumentationController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

// API Documentation
Route::get('docs', [ApiDocumentationController::class, 'index']);
Route::get('docs/json', [ApiDocumentationController::class, 'json']);
