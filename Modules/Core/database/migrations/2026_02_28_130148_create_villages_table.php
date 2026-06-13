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
            $table->string('district_id')->nullable()->index();
            $table->baseColumn();

        });

        Schema::create('history.core_village', function (Blueprint $table) {
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
        Schema::dropIfExists('history.core_village');
        Schema::dropIfExists('temporary.core_village');
        Schema::dropIfExists('core.village');
    }
};
