<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResendEmailVerificationController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\V1\CompanyController;
use App\Http\Controllers\V1\OptionController;
use App\Http\Controllers\V1\ProductController;
use App\Http\Controllers\V1\ProvinceController;
use App\Http\Controllers\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->middleware('api')->group(function () {
    Route::post('register', RegisterController::class)->name('register');
    Route::post('login', LoginController::class)->name('login');
    Route::post('forgot-password', PasswordResetLinkController::class)->name('password.email');
    Route::post('reset-password', ResetPasswordController::class)->name('password.reset');
    Route::middleware(['auth:sanctum', 'throttle:6,1'])->group(function () {
        Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)->middleware('signed')->name('verification.verify');
        Route::post('resend-email', ResendEmailVerificationController::class)->name('verification.send');
        Route::post('logout', LogoutController::class)->name('logout');
    });
});

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('users', UserController::class);
    Route::apiResource('company', CompanyController::class);
    Route::apiResource('province', ProvinceController::class);
    Route::apiResource('option', OptionController::class);
    Route::apiResource('product', ProductController::class);
});

