<?php

namespace Database\Seeders;

use App\Models\Sequence;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Sequence::updateOrCreate(
            ['seq_key' => 'demo'],
            [
                'prefix'      => 'DMO',
                'suffix'      => 'A',
                'reset_daily' => true,
            ]
        );
    }
}
