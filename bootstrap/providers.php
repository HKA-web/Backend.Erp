<?php

use App\Providers\AppServiceProvider;
use App\Providers\TenancyServiceProvider;
use Nwidart\Modules\LaravelModulesServiceProvider;

return [
    LaravelModulesServiceProvider::class,
    AppServiceProvider::class,
    TenancyServiceProvider::class,
];
