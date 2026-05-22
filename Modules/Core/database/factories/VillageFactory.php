<?php

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Models\Village;

class VillageFactory extends Factory
{
    protected $model = Village::class;

    public function definition(): array
    {
        return [
            'village_id' => Str::uuid(),
            'village_name' => fake()->name(),
            'district_id' => DB::table('core.district')->inRandomOrder()->value('district_id'),
        ];
    }
}
