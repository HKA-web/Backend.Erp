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
