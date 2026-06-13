<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

class TenantMigrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Auto-scan semua module untuk tenant migrations
        $tenantPaths = [database_path('migrations/tenant')];
        $tenantSeeders = [];
        
        foreach (glob(base_path('Modules/*')) as $modulePath) {
            $moduleName = basename($modulePath);
            
            // Tenant migrations
            $tenantMigrationPath = $modulePath . '/database/migrations/tenant';
            if (is_dir($tenantMigrationPath)) {
                $tenantPaths[] = $tenantMigrationPath;
            }
            
            // Tenant seeders
            $seederClass = "Modules\\{$moduleName}\\Database\\Seeders\\TenantDatabaseSeeder";
            if (class_exists($seederClass)) {
                $tenantSeeders[] = $seederClass;
            }
        }
        
        Config::set('tenancy.migration_parameters.--path', $tenantPaths);
        
        // Set seeder class jika ada
        if (!empty($tenantSeeders)) {
            Config::set('tenancy.dynamic_tenant_seeders', $tenantSeeders);
            Config::set('tenancy.seeder_parameters.--class', 'Database\Seeders\TenantSeeder');
        }
    }
}
