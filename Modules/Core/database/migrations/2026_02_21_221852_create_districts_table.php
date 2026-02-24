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
        Schema::createWithTemp('core.district', function (Blueprint $table) {
            $table->string('district_id')->primary();
            $table->remoteForeign('city_id', 'core.city', 'city_id');
            $table->string('district_name');
            $table->baseColumn();

        });

        Schema::create('history.core_district', function (Blueprint $table) {
            $table->uuid('history_id')->primary();
            $table->remoteForeign('executed_by', 'authentication.user', 'user_id');
            $table->string('action');
            $table->jsonb('old_data')->nullable();
            $table->jsonb('new_data')->nullable();
            $table->timestamp('executed_at')->useCurrent();
        });

        $sql = file_get_contents(__DIR__ . '/sql/2026_02_22_214655_core.procedure_action_district.sql');
        DB::unprepared($sql);

        $actions = ['lookup', 'view', 'add', 'edit', 'delete'];
        foreach ($actions as $action) {
            Permission::firstOrCreate(['name' => "core.{$action}.district", 'guard_name' => 'api']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS core.procedure_action_district");
        Schema::dropIfExists('temporary.core_district');
        Schema::dropIfExists('core.district');
    }
};
