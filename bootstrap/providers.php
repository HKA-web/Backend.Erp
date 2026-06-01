<?php

use App\Providers\AppServiceProvider;
use App\Providers\SanctumServiceProvider;
use App\Providers\TenantMigrationServiceProvider;
use App\Providers\TenancyServiceProvider;
use Nwidart\Modules\LaravelModulesServiceProvider;

return [
    LaravelModulesServiceProvider::class,
    AppServiceProvider::class,
    SanctumServiceProvider::class,
    TenantMigrationServiceProvider::class,
    TenancyServiceProvider::class,
];
