<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\CityController;
use Modules\Core\Http\Controllers\CityDraftController;
use Modules\Core\Http\Controllers\CompanyController;
use Modules\Core\Http\Controllers\CompanyDraftController;
use Modules\Core\Http\Controllers\DictionaryController;
use Modules\Core\Http\Controllers\DictionaryDraftController;
use Modules\Core\Http\Controllers\DistrictController;
use Modules\Core\Http\Controllers\DistrictDraftController;
use Modules\Core\Http\Controllers\MenuController;
use Modules\Core\Http\Controllers\MenuDraftController;
use Modules\Core\Http\Controllers\OptionController;
use Modules\Core\Http\Controllers\OptionDraftController;
use Modules\Core\Http\Controllers\ProvinceController;
use Modules\Core\Http\Controllers\ProvinceDraftController;
use Modules\Core\Http\Controllers\VillageController;
use Modules\Core\Http\Controllers\VillageDraftController;
use Modules\Core\Http\Controllers\TenantController;
use Modules\Core\Http\Controllers\DomainController;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;

// Routes for Tenant (Central database - tanpa X-Tenant header)
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::prefix('core/tenant')->controller(TenantController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
    });
    Route::prefix('core/domain')->controller(DomainController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
    });
});

// Routes for Province (Client harus kirim header: X-Tenant)
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Master Resource
    Route::prefix('core/province')->controller(ProvinceController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::put('/{id}/revise', 'revise');
        Route::delete('/{id}', 'destroy');
    });

    // Draft Resource
    Route::prefix('core/province-drafts')->controller(ProvinceDraftController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::post('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
        Route::post('/{id}/commit', 'commit');
    });
});

// Routes for City (Client harus kirim header: X-Tenant)
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Master Resource
    Route::prefix('core/city')->controller(CityController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::put('/{id}/revise', 'revise');
        Route::delete('/{id}', 'destroy');
    });

    // Draft Resource
    Route::prefix('core/city-drafts')->controller(CityDraftController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::post('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
        Route::post('/{id}/commit', 'commit');
    });
});

// Routes for District (Client harus kirim header: X-Tenant)
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Master Resource
    Route::prefix('core/district')->controller(DistrictController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::put('/{id}/revise', 'revise');
        Route::delete('/{id}', 'destroy');
    });

    // Draft Resource
    Route::prefix('core/district-drafts')->controller(DistrictDraftController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::post('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
        Route::post('/{id}/commit', 'commit');
    });
});

// Routes for Village (Client harus kirim header: X-Tenant)
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Master Resource
    Route::prefix('core/village')->controller(VillageController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::put('/{id}/revise', 'revise');
        Route::delete('/{id}', 'destroy');
    });

    // Draft Resource
    Route::prefix('core/village-drafts')->controller(VillageDraftController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::post('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
        Route::post('/{id}/commit', 'commit');
    });
});

// Routes for Company (Client harus kirim header: X-Tenant)
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Master Resource
    Route::prefix('core/company')->controller(CompanyController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::put('/{id}/revise', 'revise');
        Route::delete('/{id}', 'destroy');
    });

    // Draft Resource
    Route::prefix('core/company-drafts')->controller(CompanyDraftController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::post('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
        Route::post('/{id}/commit', 'commit');
    });
});

// Routes for Dictionary (Client harus kirim header: X-Tenant)
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Master Resource
    Route::prefix('core/dictionary')->controller(DictionaryController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::put('/{id}/revise', 'revise');
        Route::delete('/{id}', 'destroy');
    });

    // Draft Resource
    Route::prefix('core/dictionary-drafts')->controller(DictionaryDraftController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::post('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
        Route::post('/{id}/commit', 'commit');
    });
});

// Routes for core/Menu (Client harus kirim header: X-Tenant)
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Master Resource
    Route::prefix('core/menu')->controller(MenuController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::put('/{id}/revise', 'revise');
        Route::delete('/{id}', 'destroy');
    });

    // Draft Resource
    Route::prefix('core/menu-drafts')->controller(MenuDraftController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::post('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
        Route::post('/{id}/commit', 'commit');
    });
});

// Routes for Option (Client harus kirim header: X-Tenant)
Route::middleware([InitializeTenancyByRequestData::class, 'auth:sanctum'])->prefix('v1')->group(function () {
    // Master Resource
    Route::prefix('core/option')->controller(OptionController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::put('/{id}/revise', 'revise');
        Route::delete('/{id}', 'destroy');
    });

    // Draft Resource
    Route::prefix('core/option-drafts')->controller(OptionDraftController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::post('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
        Route::post('/{id}/commit', 'commit');
    });
});
