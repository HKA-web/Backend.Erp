<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product', function (Blueprint $table) {
            $table->string('product_id')->primary();
            $table->string('status')->default('draft');
            $table->boolean('is_removed')->default(false);
            $table->timestamps();
        });

        // Uncoment this code if with sequnce
        //DB::statement('
        //    DROP TRIGGER IF EXISTS before_insert_product ON \'product\';
        //');
        //DB::statement('
        //    CREATE TRIGGER before_insert_product
        //    BEFORE INSERT ON \'product\'
        //    FOR EACH ROW
        //    EXECUTE FUNCTION trg_set_pk_from_sequence();
        //');
    }

    public function down(): void
    {
        // Uncoment this code if with sequnce
        //DB::statement("DROP TRIGGER before_insert_product;");

        Schema::dropIfExists('product');
    }
};
