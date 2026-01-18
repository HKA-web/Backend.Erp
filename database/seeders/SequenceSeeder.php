<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SequenceSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('sequence')->insert([
            'seq_key'       => 'invoice',
            'prefix'        => 'INV',
            'suffix'        => 'A',
            'reset_daily'   => true,
        ]);
    }
}
