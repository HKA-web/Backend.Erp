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
        Schema::createWithTemp('core.province', function (Blueprint $table) {
            $table->string('province_id')->primary();
            $table->string('province_name');

            $table->baseColumn();

        });

        $sql = file_get_contents(__DIR__ . '/sql/2026_02_22_213729_core.procedure_action_province.sql');
        DB::unprepared($sql);

        $actions = ['lookup', 'view', 'add', 'edit', 'delete'];
        foreach ($actions as $action) {
            Permission::firstOrCreate(['name' => "core.{$action}.province", 'guard_name' => 'api']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS core.procedure_action_province");
        Schema::dropIfExists('temporary.core_province');
        Schema::dropIfExists('core.province');
    }
};
