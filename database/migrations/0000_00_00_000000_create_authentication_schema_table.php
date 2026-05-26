<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS authentication');
        DB::statement('CREATE SCHEMA IF NOT EXISTS history');
    }

    public function down(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS authentication CASCADE');
        DB::statement('DROP SCHEMA IF EXISTS history CASCADE');
    }
};
