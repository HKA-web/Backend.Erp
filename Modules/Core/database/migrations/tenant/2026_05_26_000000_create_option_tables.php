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
        Schema::createWithTemp('core.option', function (Blueprint $table) {
            $table->string('option_id')->primary();
            $table->string('option_name');
            $table->string('key');
            $table->string('value');

            $table->baseColumn();

        });

        Schema::create('history.core_option', function (Blueprint $table) {
            $table->string('history_id')->primary();
            $table->string('executed_by')->nullable();
            $table->string('action');
            $table->jsonb('old_data')->nullable();
            $table->jsonb('new_data')->nullable();
            $table->timestamp('executed_at')->useCurrent();
        });

        $sql = file_get_contents(__DIR__.'/../sql/2026_31_05_000000_core.procedures_option.sql');
        DB::unprepared($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS core.procedure_upsert_option_draft');
        DB::unprepared('DROP PROCEDURE IF EXISTS core.procedure_revise_option');
        DB::unprepared('DROP PROCEDURE IF EXISTS core.procedure_commit_option');
        Schema::dropIfExists('history.core_option');
        Schema::dropIfExists('temporary.core_option');
        Schema::dropIfExists('core.option');
    }
};
