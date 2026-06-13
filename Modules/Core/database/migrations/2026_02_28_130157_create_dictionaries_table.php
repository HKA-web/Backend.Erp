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
        Schema::createWithTemp('core.dictionary', function (Blueprint $table) {
            $table->string('dictionary_id')->primary();
            $table->string('dictionary_name');

            $table->baseColumn();

        });

        Schema::create('history.core_dictionary', function (Blueprint $table) {
            $table->string('history_id')->primary();
            $table->string('executed_by')->nullable()->index();
            $table->string('action');
            $table->jsonb('old_data')->nullable();
            $table->jsonb('new_data')->nullable();
            $table->timestamp('executed_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('history.core_dictionary');
        Schema::dropIfExists('temporary.core_dictionary');
        Schema::dropIfExists('core.dictionary');
    }
};
