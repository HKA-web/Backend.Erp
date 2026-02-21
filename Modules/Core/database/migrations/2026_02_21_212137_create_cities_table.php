<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('core.city', function (Blueprint $table) {
            $table->string('city_id')->primary();
            $table->string('city_name');
            $table->remoteForeign('province_id', 'core.province', 'province_id');
            $table->baseColumn();
        });

            $actions = ['lookup', 'view', 'add', 'edit', 'delete'];
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "core.{$action}.city", 'guard_name' => 'api']);
            }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('core.city');
    }
};
