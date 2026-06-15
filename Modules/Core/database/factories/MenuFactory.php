<?php

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Core\Models\Menu;

class MenuFactory extends Factory
{
    protected $model = Menu::class;

    public function definition(): array
    {
        $modelUpper = strtoupper('Menu');
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
            'menu_id' => Str::uuid(),
            'menu_name' => 'inventory',
            'sort_order' => 'A',
            'action' => '/inventory',
            'target' => '-',
            'interface' => 'web',
            'icon' => 'inventory',
        ];
    }
}
