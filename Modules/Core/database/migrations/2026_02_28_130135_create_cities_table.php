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

        Schema::create('history.core_city', function (Blueprint $table) {
            $table->uuid('history_id')->primary();
            $table->remoteForeign('executed_by', 'authentication.user', 'user_id');
            $table->string('action');
            $table->jsonb('old_data')->nullable();
            $table->jsonb('new_data')->nullable();
            $table->timestamp('executed_at')->useCurrent();
        });

        $sql = file_get_contents(__DIR__ . '/sql/2026_02_28_130136_core.procedures_city.sql');
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
        DB::unprepared("DROP PROCEDURE IF EXISTS core.procedure_upsert_city_draft");
        DB::unprepared("DROP PROCEDURE IF EXISTS core.procedure_revise_city");
        DB::unprepared("DROP PROCEDURE IF EXISTS core.procedure_commit_city");
        Schema::dropIfExists('history.city_history');
        Schema::dropIfExists('temporary.core_city');
        Schema::dropIfExists('core.city');
    }
};
