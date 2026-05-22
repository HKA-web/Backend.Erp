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
        Schema::createWithTemp('core.menu', function (Blueprint $table) {
            $table->string('menu_id')->primary();
            $table->remoteForeign('permission_id', 'public.auth_permissions', 'id', 'bigInteger');
            $table->string('menu_name');
            $table->string('sort_order');
            $table->string('action');
            $table->string('target');
            $table->string('interface');
            $table->string('icon');
            $table->baseColumn();
        });

        Schema::table('core.menu', function (Blueprint $table) {
            $table->selfForeign('parent_id', 'menu_id');
        });

        Schema::create('history.core_menu', function (Blueprint $table) {
            $table->uuid('history_id')->primary();
            $table->remoteForeign('executed_by', 'authentication.user', 'user_id');
            $table->string('action');
            $table->jsonb('old_data')->nullable();
            $table->jsonb('new_data')->nullable();
            $table->timestamp('executed_at')->useCurrent();
        });

        $sql = file_get_contents(__DIR__.'/sql/2026_03_17_164307_core.procedures_menu.sql');
        DB::unprepared($sql);

        $actions = ['lookup', 'view', 'add', 'edit', 'delete'];
        foreach ($actions as $action) {
            Permission::firstOrCreate(['name' => "core.{$action}.menu", 'guard_name' => 'api']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS core.procedure_upsert_menu_draft');
        DB::unprepared('DROP PROCEDURE IF EXISTS core.procedure_revise_menu');
        DB::unprepared('DROP PROCEDURE IF EXISTS core.procedure_commit_menu');
        Schema::dropIfExists('history.core_menu');
        Schema::dropIfExists('temporary.core_menu');
        Schema::dropIfExists('core.menu');
    }
};
