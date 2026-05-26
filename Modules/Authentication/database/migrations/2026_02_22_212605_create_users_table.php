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
        // Drop custom schemas untuk memastikan migrate:fresh berjalan bersih
        DB::statement('DROP SCHEMA IF EXISTS core CASCADE');
        DB::statement('DROP SCHEMA IF EXISTS history CASCADE');
        DB::statement('DROP SCHEMA IF EXISTS temporary CASCADE');

        // Recreate schemas
        DB::statement('CREATE SCHEMA IF NOT EXISTS core');
        DB::statement('CREATE SCHEMA IF NOT EXISTS history');
        DB::statement('CREATE SCHEMA IF NOT EXISTS temporary');

        Schema::createWithTemp('authentication.user', function (Blueprint $table) {
            $table->string('user_id')->primary();
            $table->string('user_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->jsonb('properties')->nullable();
            $table->boolean('enable')->default(true);
            $table->boolean('readonly')->default(false);
            $table->boolean('is_removed')->default(false);
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });

        Schema::table('authentication.user', function (Blueprint $table) {
            $table->selfForeign('created_by', 'user_id');
            $table->selfForeign('updated_by', 'user_id');
        });

        Schema::create('history.authentication_user', function (Blueprint $table) {
            $table->string('history_id')->primary();
            $table->remoteForeign('executed_by', 'authentication.user', 'user_id');
            $table->string('action');
            $table->jsonb('old_data')->nullable();
            $table->jsonb('new_data')->nullable();
            $table->timestamp('executed_at')->useCurrent();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->remoteForeign('user_id', 'authentication.user', 'user_id');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        $sql = file_get_contents(__DIR__.'/sql/2026_02_22_212605_authentication.procedures_user.sql');
        DB::unprepared($sql);

        $actions = ['lookup', 'view', 'add', 'edit', 'delete'];
        foreach ($actions as $action) {
            Permission::firstOrCreate(['name' => "authentication.{$action}.user", 'guard_name' => 'api']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS authentication.procedure_upsert_user_draft');
        DB::unprepared('DROP PROCEDURE IF EXISTS authentication.procedure_revise_user');
        DB::unprepared('DROP PROCEDURE IF EXISTS authentication.procedure_commit_user');
        Schema::dropIfExists('history.authentication_user');
        Schema::dropIfExists('temporary.authentication_user');
        Schema::dropIfExists('authentication.user');
    }
};
