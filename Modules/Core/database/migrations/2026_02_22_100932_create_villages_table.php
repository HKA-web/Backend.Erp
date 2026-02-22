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
        Schema::createWithTemp('core.village', function (Blueprint $table) {
            $table->string('village_id')->primary();
            $table->string('village_name');
            $table->remoteForeign('district_id', 'core.district', 'district_id');
            $table->baseColumn();

        });

        $sql = file_get_contents(__DIR__ . '/sql/2026_02_22_214831_core.procedure_action_village.sql');
        DB::unprepared($sql);

        $actions = ['lookup', 'view', 'add', 'edit', 'delete'];
        foreach ($actions as $action) {
            Permission::firstOrCreate(['name' => "core.{$action}.village", 'guard_name' => 'api']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS core.procedure_action_village");
        Schema::dropIfExists('temporary.core_village');
        Schema::dropIfExists('core.village');
    }
};
