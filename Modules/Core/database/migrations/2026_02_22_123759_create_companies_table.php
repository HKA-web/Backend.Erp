<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::createWithTemp('core.company', function (Blueprint $table) {
            $table->string('company_id')->primary();
            $table->string('company_name');

            $table->baseColumn();

        });

        $sql = file_get_contents(__DIR__ . '/sql/2026_02_22_123759_core.push_company.sql');
        DB::unprepared($sql);

        $actions = ['lookup', 'view', 'add', 'edit', 'delete'];
        foreach ($actions as $action) {
            Permission::firstOrCreate(['name' => "core.{$action}.company", 'guard_name' => 'api']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS core.push_company");
        Schema::dropIfExists('temporary.core_company');
        Schema::dropIfExists('core.company');
    }
};
