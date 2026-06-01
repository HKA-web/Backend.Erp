<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ApiDocumentationController;
use Illuminate\Support\Facades\Route;

// Public routes (tanpa tenancy)
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// API Documentation (tanpa tenancy)
Route::get('docs', [ApiDocumentationController::class, 'index']);
Route::get('docs/json', [ApiDocumentationController::class, 'json']);

// Authenticated routes (tanpa tenancy karena di database central)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});
