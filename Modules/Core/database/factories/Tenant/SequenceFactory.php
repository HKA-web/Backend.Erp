<?php

namespace Modules\Core\Database\Factories\Tenant;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Core\Models\Sequence;

class SequenceFactory extends Factory
{
    protected $model = Sequence::class;

    public function definition(): array
    {
        $modelUpper = strtoupper('Sequence');
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
            'sequence_id'   => \Illuminate\Support\Str::uuid(),
            'sequence_name' => fake()->name(),
        ];
    }
}
