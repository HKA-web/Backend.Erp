<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Http\Controllers\UserController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('authentication/user', UserController::class)->names('authentication-user');
});
