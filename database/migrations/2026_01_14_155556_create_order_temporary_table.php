<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_temporary', function (Blueprint $table) {
            $table->bigIncrements('temporary_id');
            $table->string('order_id')->nullable();
            $table->string('status')->nullable();
            $table->string('session_id')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_temporary');
    }
};