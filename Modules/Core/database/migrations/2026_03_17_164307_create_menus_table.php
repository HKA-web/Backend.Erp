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
            $table->bigInteger('permission_id')->nullable()->index();
            $table->string('menu_name');
            $table->string('sort_order');
            $table->string('action');
            $table->string('target');
            $table->string('interface');
            $table->string('icon');
            $table->baseColumn();
        });

        Schema::table('core.menu', function (Blueprint $table) {
            $table->string('parent_id')->nullable()->index();
        });

        Schema::create('history.core_menu', function (Blueprint $table) {
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
        Schema::dropIfExists('history.core_menu');
        Schema::dropIfExists('temporary.core_menu');
        Schema::dropIfExists('core.menu');
    }
};
