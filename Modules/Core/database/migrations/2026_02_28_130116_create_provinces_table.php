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

        Schema::create('history.core_province', function (Blueprint $table) {
            $table->uuid('history_id')->primary();
            $table->remoteForeign('executed_by', 'authentication.user', 'user_id');
            $table->string('action');
            $table->jsonb('old_data')->nullable();
            $table->jsonb('new_data')->nullable();
            $table->timestamp('executed_at')->useCurrent();
        });

        $sql = file_get_contents(__DIR__ . '/sql/2026_02_28_130116_core.procedures_province.sql');
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
        DB::unprepared("DROP PROCEDURE IF EXISTS core.procedure_upsert_province_draft");
        DB::unprepared("DROP PROCEDURE IF EXISTS core.procedure_revise_province");
        DB::unprepared("DROP PROCEDURE IF EXISTS core.procedure_commit_province");
        Schema::dropIfExists('history.province_history');
        Schema::dropIfExists('temporary.core_province');
        Schema::dropIfExists('core.province');
    }
};
