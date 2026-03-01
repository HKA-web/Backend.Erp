<?php

use Modules\Core\Http\Controllers\DictionaryController;
use Modules\Core\Http\Controllers\DictionaryDraftController;

use Modules\Core\Http\Controllers\CompanyController;
use Modules\Core\Http\Controllers\CompanyDraftController;

use Modules\Core\Http\Controllers\VillageController;
use Modules\Core\Http\Controllers\VillageDraftController;

use Modules\Core\Http\Controllers\DistrictController;
use Modules\Core\Http\Controllers\DistrictDraftController;

use Modules\Core\Http\Controllers\CityController;
use Modules\Core\Http\Controllers\CityDraftController;

use Modules\Core\Http\Controllers\ProvinceController;
use Modules\Core\Http\Controllers\ProvinceDraftController;

use Illuminate\Support\Facades\Route;

// Routes for Province
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Master Resource
    Route::prefix('core/province')->group(function () {
        Route::get('/', [ProvinceController::class, 'index']);
        Route::get('/{id}', [ProvinceController::class, 'show']);
        Route::post('/{id}/revise', [ProvinceController::class, 'revise']);
        Route::delete('/{id}', [ProvinceController::class, 'destroy']);
    });

    // Draft Resource
    Route::prefix('core/province-drafts')->group(function () {
        Route::get('/', [ProvinceDraftController::class, 'index']);
        Route::post('/', [ProvinceDraftController::class, 'store']);
        Route::get('/{id}', [ProvinceDraftController::class, 'show']);
        Route::put('/{id}', [ProvinceDraftController::class, 'update']);
        Route::delete('/{id}', [ProvinceDraftController::class, 'destroy']);
        Route::post('/{id}/commit', [ProvinceDraftController::class, 'commit']);
    });
});

// Routes for City
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Master Resource
    Route::prefix('core/city')->group(function () {
        Route::get('/', [CityController::class, 'index']);
        Route::get('/{id}', [CityController::class, 'show']);
        Route::post('/{id}/revise', [CityController::class, 'revise']);
        Route::delete('/{id}', [CityController::class, 'destroy']);
    });

    // Draft Resource
    Route::prefix('core/city-drafts')->group(function () {
        Route::get('/', [CityDraftController::class, 'index']);
        Route::post('/', [CityDraftController::class, 'store']);
        Route::get('/{id}', [CityDraftController::class, 'show']);
        Route::put('/{id}', [CityDraftController::class, 'update']);
        Route::delete('/{id}', [CityDraftController::class, 'destroy']);
        Route::post('/{id}/commit', [CityDraftController::class, 'commit']);
    });
});

// Routes for District
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Master Resource
    Route::prefix('core/district')->group(function () {
        Route::get('/', [DistrictController::class, 'index']);
        Route::get('/{id}', [DistrictController::class, 'show']);
        Route::post('/{id}/revise', [DistrictController::class, 'revise']);
        Route::delete('/{id}', [DistrictController::class, 'destroy']);
    });

    // Draft Resource
    Route::prefix('core/district-drafts')->group(function () {
        Route::get('/', [DistrictDraftController::class, 'index']);
        Route::post('/', [DistrictDraftController::class, 'store']);
        Route::get('/{id}', [DistrictDraftController::class, 'show']);
        Route::put('/{id}', [DistrictDraftController::class, 'update']);
        Route::delete('/{id}', [DistrictDraftController::class, 'destroy']);
        Route::post('/{id}/commit', [DistrictDraftController::class, 'commit']);
    });
});

// Routes for Village
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Master Resource
    Route::prefix('core/village')->group(function () {
        Route::get('/', [VillageController::class, 'index']);
        Route::get('/{id}', [VillageController::class, 'show']);
        Route::post('/{id}/revise', [VillageController::class, 'revise']);
        Route::delete('/{id}', [VillageController::class, 'destroy']);
    });

    // Draft Resource
    Route::prefix('core/village-drafts')->group(function () {
        Route::get('/', [VillageDraftController::class, 'index']);
        Route::post('/', [VillageDraftController::class, 'store']);
        Route::get('/{id}', [VillageDraftController::class, 'show']);
        Route::put('/{id}', [VillageDraftController::class, 'update']);
        Route::delete('/{id}', [VillageDraftController::class, 'destroy']);
        Route::post('/{id}/commit', [VillageDraftController::class, 'commit']);
    });
});

// Routes for Company
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Master Resource
    Route::prefix('core/company')->group(function () {
        Route::get('/', [CompanyController::class, 'index']);
        Route::get('/{id}', [CompanyController::class, 'show']);
        Route::post('/{id}/revise', [CompanyController::class, 'revise']);
        Route::delete('/{id}', [CompanyController::class, 'destroy']);
    });

    // Draft Resource
    Route::prefix('core/company-drafts')->group(function () {
        Route::get('/', [CompanyDraftController::class, 'index']);
        Route::post('/', [CompanyDraftController::class, 'store']);
        Route::get('/{id}', [CompanyDraftController::class, 'show']);
        Route::put('/{id}', [CompanyDraftController::class, 'update']);
        Route::delete('/{id}', [CompanyDraftController::class, 'destroy']);
        Route::post('/{id}/commit', [CompanyDraftController::class, 'commit']);
    });
});

// Routes for Dictionary
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Master Resource
    Route::prefix('core/dictionary')->group(function () {
        Route::get('/', [DictionaryController::class, 'index']);
        Route::get('/{id}', [DictionaryController::class, 'show']);
        Route::post('/{id}/revise', [DictionaryController::class, 'revise']);
        Route::delete('/{id}', [DictionaryController::class, 'destroy']);
    });

    // Draft Resource
    Route::prefix('core/dictionary-drafts')->group(function () {
        Route::get('/', [DictionaryDraftController::class, 'index']);
        Route::post('/', [DictionaryDraftController::class, 'store']);
        Route::get('/{id}', [DictionaryDraftController::class, 'show']);
        Route::put('/{id}', [DictionaryDraftController::class, 'update']);
        Route::delete('/{id}', [DictionaryDraftController::class, 'destroy']);
        Route::post('/{id}/commit', [DictionaryDraftController::class, 'commit']);
    });
});
