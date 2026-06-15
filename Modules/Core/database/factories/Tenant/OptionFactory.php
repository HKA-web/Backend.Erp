<?php

namespace Modules\Core\Database\Factories\Tenant;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\Option;

class OptionFactory extends Factory
{
    protected $model = Option::class;

    public function definition(): array
    {
        $modelUpper = strtoupper('Option');
        $moduleUpper = strtoupper('Core');
        
        \Modules\Core\Models\Sequence::firstOrCreate(
            ['sequence_name' => $modelUpper],
            [
                'sequence_id' => \Illuminate\Support\Str::uuid(),
                'prefix' => "{$modelUpper}-{YYYY}{MM}-",
                'suffix' => "{$moduleUpper}",
                'padding' => 4,
                'current_number' => 0,
                'reset_type' => 'MONTHLY',
                'last_reset_date' => now(),
            ]
        );

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
