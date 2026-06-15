<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Schema::createWithTemp('core.sequence', function (Blueprint $table) {
            $table->string('sequence_id')->primary();
            $table->string('sequence_name')->unique();
            $table->string('prefix')->nullable();
            $table->string('suffix')->nullable();
            $table->integer('padding')->default(4);
            $table->integer('current_number')->default(0);
            $table->string('reset_type', 20)->default('NONE'); // NONE, YEARLY, MONTHLY, DAILY
            $table->date('last_reset_date')->nullable();

            $table->baseColumn();
        });

        Schema::create('history.core_sequence', function (Blueprint $table) {
            $table->string('history_id')->primary();
            $table->string('executed_by');
            $table->string('action');
            $table->jsonb('old_data')->nullable();
            $table->jsonb('new_data')->nullable();
            $table->timestamp('executed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS core.procedure_upsert_sequence_draft");
        DB::unprepared("DROP PROCEDURE IF EXISTS core.procedure_revise_sequence");
        DB::unprepared("DROP PROCEDURE IF EXISTS core.procedure_commit_sequence");
        Schema::dropIfExists('history.core_sequence');
        Schema::dropIfExists('temporary.core_sequence');
        Schema::dropIfExists('core.sequence');
    }
};
