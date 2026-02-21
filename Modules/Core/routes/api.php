<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\CityController;
use Modules\Core\Http\Controllers\ProvinceController;
use Modules\Core\Http\Controllers\DistrictController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('core/province', ProvinceController::class)->names('core-province');
});

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('core/city', CityController::class)->names('core-city');
});

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('core/{model}', DistrictController::class)->names('core-district');
 });
