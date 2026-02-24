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
        Schema::createWithTemp('core.company', function (Blueprint $table) {
            $table->string('company_id')->primary();
            $table->remoteForeign('province_id', 'core.province', 'province_id');
            $table->remoteForeign('city_id', 'core.city', 'city_id');
            $table->remoteForeign('district_id', 'core.district', 'district_id');
            $table->remoteForeign('village_id', 'core.village', 'village_id');
            $table->string('company_name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('address');
            $table->string('website');
            $table->baseColumn();

        });

        Schema::create('history.core_company', function (Blueprint $table) {
            $table->uuid('history_id')->primary();
            $table->remoteForeign('executed_by', 'authentication.user', 'user_id');
            $table->string('action');
            $table->jsonb('old_data')->nullable();
            $table->jsonb('new_data')->nullable();
            $table->timestamp('executed_at')->useCurrent();
        });

        $sql = file_get_contents(__DIR__ . '/sql/2026_02_22_215101_core.procedure_action_company.sql');
        DB::unprepared($sql);

        $actions = ['lookup', 'view', 'add', 'edit', 'delete'];
        foreach ($actions as $action) {
            Permission::firstOrCreate(['name' => "core.{$action}.company", 'guard_name' => 'api']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS core.procedure_action_company");
        Schema::dropIfExists('temporary.core_company');
        Schema::dropIfExists('core.company');
    }
};
