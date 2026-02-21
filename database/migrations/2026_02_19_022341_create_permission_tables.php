<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $teams = config('permission.teams');
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        // Menggunakan nama kolom custom dari config
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $modelKey = $columnNames['model_morph_key'] ?? 'model_id';

        throw_if(empty($tableNames), 'Error: config/permission.php not loaded.');

        // 1. Tabel Permissions (auth_permissions)
        Schema::create($tableNames['permissions'], static function (Blueprint $table) {
            $table->id(); // Tetap 'id' agar internal Spatie tidak break
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        // 2. Tabel Roles (auth_group)
        Schema::create($tableNames['roles'], static function (Blueprint $table) use ($teams, $columnNames) {
            $table->id(); // Tetap 'id' agar internal Spatie tidak break
            if ($teams || config('permission.testing')) {
                $table->unsignedBigInteger($columnNames['team_foreign_key'])->nullable();
                $table->index($columnNames['team_foreign_key'], 'roles_team_foreign_key_index');
            }
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            if ($teams || config('permission.testing')) {
                $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
        });

        // 3. Tabel User Has Permissions (authentication.user_permissions)
        Schema::create($tableNames['model_has_permissions'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams, $modelKey) {
            $table->unsignedBigInteger($pivotPermission); // auth_permission_id

            $table->string('model_type');
            $table->string($modelKey); // user_id (String PK)
            $table->index([$modelKey, 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->cascadeOnDelete();

            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->primary([$columnNames['team_foreign_key'], $pivotPermission, $modelKey, 'model_type'], 'model_has_permissions_permission_model_type_primary');
            } else {
                $table->primary([$pivotPermission, $modelKey, 'model_type'], 'model_has_permissions_permission_model_type_primary');
            }
        });

        // 4. Tabel User Has Groups (authentication.user_groups)
        Schema::create($tableNames['model_has_roles'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams, $modelKey) {
            $table->unsignedBigInteger($pivotRole); // auth_group_id

            $table->string('model_type');
            $table->string($modelKey); // user_id (String PK)
            $table->index([$modelKey, 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->cascadeOnDelete();

            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->primary([$columnNames['team_foreign_key'], $pivotRole, $modelKey, 'model_type'], 'model_has_roles_role_model_type_primary');
            } else {
                $table->primary([$pivotRole, $modelKey, 'model_type'], 'model_has_roles_role_model_type_primary');
            }
        });

        // 5. Tabel Group Has Permissions (authentication.group_permissions)
        Schema::create($tableNames['role_has_permissions'], static function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
            $table->unsignedBigInteger($pivotPermission); // auth_permission_id
            $table->unsignedBigInteger($pivotRole); // auth_group_id

            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->cascadeOnDelete();

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->cascadeOnDelete();

            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });

        app('cache')->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');

        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['roles']);
        Schema::dropIfExists($tableNames['permissions']);
    }
};
