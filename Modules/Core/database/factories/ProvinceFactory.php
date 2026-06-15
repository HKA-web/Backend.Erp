<?php

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Core\Models\Province;

class ProvinceFactory extends Factory
{
    protected $model = Province::class;

    public function definition(): array
    {
        $modelUpper = strtoupper('Province');
        $moduleUpper = strtoupper('Core');
        
        \Modules\Core\Models\Sequence::firstOrCreate(
            ['sequence_name' => $modelUpper],
            [
                'sequence_id' => \Illuminate\Support\Str::uuid(),
                'prefix' => "{$modelUpper}-{YYYY}{MM}-",
                'suffix' => "-{$moduleUpper}",
                'padding' => 4,
                'current_number' => 0,
                'reset_type' => 'MONTHLY',
                'last_reset_date' => now(),
            ]
        );

        return [
            'province_id' => Str::uuid(),
            'province_name' => fake()->name(),
        ];
    }
}
