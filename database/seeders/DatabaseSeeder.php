<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Authentication\Database\Seeders\AuthenticationDatabaseSeeder;
use Modules\Core\Database\Seeders\CoreDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AuthenticationDatabaseSeeder::class,
            CoreDatabaseSeeder::class,
        ]);
    }
}
