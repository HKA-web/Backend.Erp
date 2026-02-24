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
        Schema::createWithTemp('core.dictionary', function (Blueprint $table) {
            $table->string('dictionary_id')->primary();
            $table->remoteForeign('company_id', 'core.company', 'company_id');
            $table->string('dictionary_name');
            $table->string('key');
            $table->baseColumn();

        });

        Schema::create('history.core_dictionary', function (Blueprint $table) {
            $table->uuid('history_id')->primary();
            $table->remoteForeign('executed_by', 'authentication.user', 'user_id');
            $table->string('action');
            $table->jsonb('old_data')->nullable();
            $table->jsonb('new_data')->nullable();
            $table->timestamp('executed_at')->useCurrent();
        });

        $sql = file_get_contents(__DIR__ . '/sql/2026_02_24_115645_core.procedure_action_dictionary.sql');
        DB::unprepared($sql);

        $actions = ['lookup', 'view', 'add', 'edit', 'delete'];
        foreach ($actions as $action) {
            Permission::firstOrCreate(['name' => "core.{$action}.dictionary", 'guard_name' => 'api']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS core.procedure_action_dictionary");
        Schema::dropIfExists('history.dictionary_history');
        Schema::dropIfExists('temporary.core_dictionary');
        Schema::dropIfExists('core.dictionary');
    }
};
