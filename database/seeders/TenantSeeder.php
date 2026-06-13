<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $seeders = Config::get('tenancy.dynamic_tenant_seeders', []);

        if (empty($seeders)) {
            $this->command->info('No tenant seeders found to run.');
            return;
        }

        foreach ($seeders as $seeder) {
            $this->command->info("Running: {$seeder}");
            $this->call($seeder);
        }
    }
}
