<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        \Modules\Core\Models\Option::factory()->count(10)->create();

        $this->command->info('Tenant database seeded successfully using factories.');
    }
}
