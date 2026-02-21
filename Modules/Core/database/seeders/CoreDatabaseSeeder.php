<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\City;
use Modules\Core\Models\District;
use Modules\Core\Models\Province;

class CoreDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Province::factory()->create();
        City::factory()->create();
        District::factory()->create();
    }
}
