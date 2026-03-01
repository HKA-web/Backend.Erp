<?php

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Models\City;

class CityFactory extends Factory
{
    protected $model = City::class;

    public function definition(): array
    {
        return [
            'city_id'   => Str::uuid(),
            'city_name' => fake()->name(),
            'province_id' => DB::table('core.province')->inRandomOrder()->value('province_id'),
        ];
    }
}
