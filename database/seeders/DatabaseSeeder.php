<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Authentication\Database\Seeders\CentralSeeder as AuthCentralSeeder;
use Modules\Core\Database\Seeders\CentralSeeder as CoreCentralSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AuthCentralSeeder::class,
            CoreCentralSeeder::class,
        ]);
    }
}
