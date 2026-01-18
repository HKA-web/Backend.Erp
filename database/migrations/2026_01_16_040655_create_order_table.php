<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order', function (Blueprint $table) {
            $table->string('order_id')->primary();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        // Uncoment this code if with sequnce
        DB::statement('
            DROP TRIGGER IF EXISTS before_insert_order ON \'order\';
            CREATE TRIGGER before_insert_order
            BEFORE INSERT ON "order"
            FOR EACH ROW
            EXECUTE FUNCTION trg_set_pk_from_sequence(\'order\', \'order_id\');
        ');
    }

    public function down(): void
    {
        // Uncoment this code if with sequnce
        DB::statement("DROP TRIGGER before_insert_order;");

        Schema::dropIfExists('order');
    }
};
