<?php

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Models\District;

class DistrictFactory extends Factory
{
    protected $model = District::class;

    public function definition(): array
    {
        return [
            'district_id'   => Str::uuid(),
            'district_name' => fake()->name(),
            'city_id' => DB::table('core.city')->inRandomOrder()->value('city_id'),
        ];
    }
}
