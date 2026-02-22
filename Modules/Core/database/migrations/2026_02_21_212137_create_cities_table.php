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
        Schema::createWithTemp('core.city', function (Blueprint $table) {
            $table->string('city_id')->primary();
            $table->string('city_name');
            $table->remoteForeign('province_id', 'core.province', 'province_id');
            $table->baseColumn();

        });

        $sql = file_get_contents(__DIR__ . '/sql/2026_02_22_214419_core.procedure_action_city.sql');
        DB::unprepared($sql);

        $actions = ['lookup', 'view', 'add', 'edit', 'delete'];
        foreach ($actions as $action) {
            Permission::firstOrCreate(['name' => "core.{$action}.city", 'guard_name' => 'api']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS core.procedure_action_city");
        Schema::dropIfExists('temporary.core_city');
        Schema::dropIfExists('core.city');
    }
};
