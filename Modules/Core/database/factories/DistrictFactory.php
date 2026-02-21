<?php

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Core\Models\District;

class DistrictFactory extends Factory
{
    protected $model = District::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'district_id'   => Str::uuid(),
            'district_name' => fake()->name(),
        ];
    }
}
