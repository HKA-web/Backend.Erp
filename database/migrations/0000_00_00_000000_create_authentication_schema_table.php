<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS authentication');
    }

    public function down(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS authentication CASCADE');
    }
};
