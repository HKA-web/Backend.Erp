<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS core');

        if (!Schema::hasTable('core.province')) {
            Schema::create('core.province', function (Blueprint $table) {
                $table->string('province_id', 50)->primary();

                $table->string('province_name', 100)->comment('Nama provinsi');
                $table->string('province_code', 10)->nullable()->comment('Kode provinsi');

                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('core.province');
        DB::statement('DROP SCHEMA IF EXISTS core CASCADE');
    }
};
