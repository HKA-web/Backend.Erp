<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;

class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $seederPath = base_path('Modules/Core/database/seeders/tenant');
        $seederFiles = glob($seederPath . '/*.php');

        $seeders = [];
        foreach ($seederFiles as $file) {
            $className = basename($file, '.php');
            $fullClassName = 'Modules\\Core\\Database\\Seeders\\Tenant\\' . $className;

            if ($className === 'TenantDatabaseSeeder') {
                continue;
            }

            if (!class_exists($fullClassName)) {
                continue;
            }

            $seeders[] = $fullClassName;
        }

        sort($seeders);

        if (!empty($seeders)) {
            $this->call($seeders);
        }

        $this->command->info('Tenant database seeded successfully. (' . count($seeders) . ' seeder(s) executed)');
    }
}
