<?php

namespace Modules\Core\Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Modules\Core\Models\Option;

class OptionSeeder extends Seeder
{
    public function run(): void
    {
        Option::factory()->count(10)->create();
        
        $this->command->info('Options seeded successfully using factory.');
    }
}
