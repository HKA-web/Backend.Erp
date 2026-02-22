<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\CityController;
use Modules\Core\Http\Controllers\CompanyController;
use Modules\Core\Http\Controllers\DistrictController;
use Modules\Core\Http\Controllers\ProvinceController;
use Modules\Core\Http\Controllers\VillageController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('core/province', ProvinceController::class)->names('core-province');
});

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('core/city', CityController::class)->names('core-city');
});

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('core/district', DistrictController::class)->names('core-district');
 });

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('core/village', VillageController::class)->names('core-village');
});

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('core/company', CompanyController::class)->names('core-company');
});
