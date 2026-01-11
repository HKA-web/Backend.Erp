<?php

use Illuminate\Support\Facades\Route;

Route::get(uri: '/', action: function () {
    return [
        'laravel' => app()->version(),
        'php' => PHP_VERSION,
    ];
});
