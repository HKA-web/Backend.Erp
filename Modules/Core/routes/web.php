<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\ProvinceController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('cores', ProvinceController::class)->names('core');
});
