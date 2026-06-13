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
        Schema::createWithTemp('core.company', function (Blueprint $table) {
            $table->string('company_id')->primary();
            $table->string('tenant_id')->nullable(); // Reference ke tabel tenants (Stancl Tenancy)
            $table->string('province_id')->nullable()->index();
            $table->string('city_id')->nullable()->index();
            $table->string('district_id')->nullable()->index();
            $table->string('village_id')->nullable()->index();
            $table->string('company_name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('address');
            $table->string('website');
            $table->baseColumn();

        });

        Schema::create('history.core_company', function (Blueprint $table) {
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
        Schema::dropIfExists('history.core_company');
        Schema::dropIfExists('temporary.core_company');
        Schema::dropIfExists('core.company');
    }
};
