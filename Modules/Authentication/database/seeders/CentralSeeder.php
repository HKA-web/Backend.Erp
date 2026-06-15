<?php

namespace Modules\Authentication\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Authentication\Models\User;

class CentralSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create();
    }
}
