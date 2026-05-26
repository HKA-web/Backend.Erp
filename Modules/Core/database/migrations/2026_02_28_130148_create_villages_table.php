<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        Schema::create('history.core_village', function (Blueprint $table) {
            $table->string('history_id')->primary();
            $table->remoteForeign('executed_by', 'authentication.user', 'user_id');
            $table->string('action');
            $table->jsonb('old_data')->nullable();
            $table->jsonb('new_data')->nullable();
            $table->timestamp('executed_at')->useCurrent();
        });

        $sql = file_get_contents(__DIR__.'/sql/2026_02_28_130148_core.procedures_village.sql');
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
        DB::unprepared('DROP PROCEDURE IF EXISTS core.procedure_upsert_village_draft');
        DB::unprepared('DROP PROCEDURE IF EXISTS core.procedure_revise_village');
        DB::unprepared('DROP PROCEDURE IF EXISTS core.procedure_commit_village');
        Schema::dropIfExists('history.core_village');
        Schema::dropIfExists('temporary.core_village');
        Schema::dropIfExists('core.village');
    }
};
