<?php

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\Option;

class OptionFactory extends Factory
{
    protected $model = Option::class;

    public function definition(): array
    {
        return [
            'option_id' => fake()->uuid(),
            'option_name' => fake()->word(),
            'key' => fake()->word(),
            'value' => fake()->word(),
            'properties' => null,
            'enable' => true,
            'readonly' => false,
            'is_removed' => false,
            'created_by' => null,
            'updated_by' => null,
            'status' => 'POSTED',
        ];
    }
}
