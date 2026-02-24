<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ErpModelMakeCommand extends Command
{
    protected $signature = 'erp:make-model {model} {module}';

    public function handle()
    {
        $model = Str::studly($this->argument('model'));
        $module = Str::studly($this->argument('module'));

        $vars = [
            '{{model}}'        => $model,
            '{{module}}'       => $module,
            '{{module_lower}}' => Str::lower($module),
            '{{model_lower}}'  => Str::lower($model),
            '{{schema}}'       => Str::lower($module),
        ];

        $this->info("🚀 Generating ERP Components for {$model}...");

        Artisan::call("module:make-migration create_".Str::snake(Str::plural($model))."_table {$module}");

        $this->generateFromStub('erp-stubs/controller', base_path("Modules/{$module}/app/Http/Controllers/{$model}Controller.php"), $vars);
        $this->generateFromStub('erp-stubs/requests', base_path("Modules/{$module}/app/Http/Requests/{$model}Request.php"), $vars);
        $this->generateFromStub('erp-stubs/model', base_path("Modules/{$module}/app/Models/{$model}.php"), $vars);
        $this->generateFromStub('erp-stubs/factory', base_path("Modules/{$module}/database/factories/{$model}Factory.php"), $vars);

        $this->injectMigration($module, $vars['{{schema}}'], $model);

        $this->info("✅ Success! Files created exactly as requested.");
    }

    protected function generateFromStub($stubName, $dest, $vars)
    {
        $stubPath = base_path("stubs/{$stubName}.stub");

        if (!File::exists($stubPath)) {
            $this->error("Stub not found: {$stubPath}");
            return;
        }

        $content = File::get($stubPath);
        $content = str_replace(array_keys($vars), array_values($vars), $content);

        File::ensureDirectoryExists(dirname($dest));

        if (File::put($dest, $content)) {
            $this->line("Created: <info>{$dest}</info>");
        } else {
            $this->error("FAILED to create: {$dest}. Check folder permissions!");
        }
    }

    protected function injectMigration($module, $schema, $model)
    {
        $dir = base_path("Modules/{$module}/Database/Migrations");
        if (!File::isDirectory($dir)) $dir = base_path("Modules/{$module}/database/migrations");

        $latestFile = collect(File::files($dir))->sortByDesc(fn($f) => $f->getMTime())->first();

        if ($latestFile) {
            $content = File::get($latestFile->getRealPath());

            $modelLower  = Str::lower($model);
            $schemaLower = Str::lower($schema);
            $fullTableName = "{$schemaLower}.{$modelLower}";
            $tempTableName = "temporary.{$schemaLower}_{$modelLower}";

            $sqlDir = $dir . "/sql";
            if (!File::isDirectory($sqlDir)) File::makeDirectory($sqlDir, 0755, true);

            $timestamp   = date('Y_m_d_His');
            $sqlFileName = "{$timestamp}_{$schemaLower}.procedure_action_{$modelLower}.sql";
            $sqlFilePath = $sqlDir . "/" . $sqlFileName;

            $sqlContent = <<<SQL
DROP PROCEDURE IF EXISTS {$schemaLower}.procedure_action_{$modelLower};
CREATE OR REPLACE PROCEDURE {$schemaLower}.procedure_action_{$modelLower}(
    p_session_id UUID,
    p_payload JSONB
)
    LANGUAGE plpgsql
AS
$$
DECLARE
    v_existing_session_id UUID;
    v_status        TEXT;
    v_{$modelLower}_id TEXT;
    v_is_removed    BOOLEAN;
    v_old_data      JSONB;
    v_new_data      JSONB;
    v_user_id       UUID;
BEGIN
    -- 1. Ambil data dasar dari payload
    v_status        := COALESCE(p_payload ->> 'status', 'DRAFT');
    v_{$modelLower}_id := p_payload ->> '{$modelLower}_id';
    v_user_id       := (p_payload ->> 'user_id')::UUID;
    v_is_removed    := COALESCE((p_payload ->> 'is_removed')::BOOLEAN, FALSE);

    -- 2. Cek apakah data sudah ada di tabel temporary dengan session berbeda (Locking Mechanism)
    SELECT session_id INTO v_existing_session_id
    FROM temporary.{$schemaLower}_{$modelLower}
    WHERE {$modelLower}_id = v_{$modelLower}_id
    LIMIT 1;

    -- 3. LOGIKA POSTED (COMMIT ACTION)
    IF v_status = 'POSTED' THEN
        IF EXISTS (SELECT 1 FROM temporary.{$schemaLower}_{$modelLower} WHERE {$modelLower}_id = v_{$modelLower}_id AND session_id = p_session_id) THEN

            -- A. Ambil snapshot data lama dari Master
            SELECT to_jsonb(t) INTO v_old_data FROM {$schemaLower}.{$modelLower} t WHERE t.{$modelLower}_id = v_{$modelLower}_id;

            -- B. Jalankan UPSERT ke Master
            INSERT INTO {$schemaLower}.{$modelLower} (
                {$modelLower}_id,
                {$modelLower}_name,
                is_removed,
                created_at,
                updated_at,
                status
            )
            SELECT
                {$modelLower}_id,
                {$modelLower}_name,
                is_removed,
                NOW(),
                NOW(),
                'POSTED'
            FROM temporary.{$schemaLower}_{$modelLower}
            WHERE {$modelLower}_id = v_{$modelLower}_id AND session_id = p_session_id
            ON CONFLICT ({$modelLower}_id)
                DO UPDATE SET
                    {$modelLower}_name = EXCLUDED.{$modelLower}_name,
                    status = EXCLUDED.status,
                    is_removed = EXCLUDED.is_removed,
                    updated_at = NOW();

            -- C. Ambil snapshot data baru setelah UPSERT
            SELECT to_jsonb(t) INTO v_new_data FROM {$schemaLower}.{$modelLower} t WHERE t.{$modelLower}_id = v_{$modelLower}_id;

            -- D. INSERT KE HISTORY
            INSERT INTO history.{$modelLower}_history (
                history_id,
                executed_by,
                action,
                old_data,
                new_data,
                executed_at
            ) VALUES (
                gen_random_uuid(),
                v_user_id,
                CASE
                    WHEN v_is_removed = TRUE THEN 'DELETE'
                    WHEN v_old_data IS NULL THEN 'INSERT'
                    ELSE 'UPDATE'
                END,
                v_old_data,
                v_new_data,
                NOW()
            );

            -- E. Bersihkan tabel temporary
            DELETE FROM temporary.{$schemaLower}_{$modelLower}
            WHERE {$modelLower}_id = v_{$modelLower}_id AND session_id = p_session_id;

        ELSE
            RAISE EXCEPTION 'ERROR PROCEDURE: Data not found in temporary for ID % in this session.', v_{$modelLower}_id;
        END IF;

    -- 4. LOGIKA EDIT
    ELSIF v_status = 'EDIT' THEN
        IF v_existing_session_id IS NOT NULL AND v_existing_session_id <> p_session_id THEN
            RAISE EXCEPTION 'Data already processed by another user (Session: %)', v_existing_session_id;
        ELSE
            WITH source_data AS (
                SELECT *, p_session_id as session_id
                FROM {$schemaLower}.{$modelLower}
                WHERE {$modelLower}_id = v_{$modelLower}_id
            )
            INSERT INTO temporary.{$schemaLower}_{$modelLower}
            SELECT * FROM source_data
            ON CONFLICT ({$modelLower}_id) DO NOTHING;

            UPDATE temporary.{$schemaLower}_{$modelLower}
            SET is_removed = FALSE, status = 'DRAFT'
            WHERE {$modelLower}_id = v_{$modelLower}_id AND session_id = p_session_id;
        END IF;

    -- 5. LOGIKA DELETE
    ELSIF v_status = 'DELETE' THEN
        IF v_existing_session_id IS NOT NULL AND v_existing_session_id <> p_session_id THEN
            RAISE EXCEPTION 'Data already processed by another user (Session: %)', v_existing_session_id;
        ELSE
            WITH source_data AS (
                SELECT *, p_session_id as session_id
                FROM {$schemaLower}.{$modelLower}
                WHERE {$modelLower}_id = v_{$modelLower}_id
            )
            INSERT INTO temporary.{$schemaLower}_{$modelLower}
            SELECT * FROM source_data
            ON CONFLICT ({$modelLower}_id) DO NOTHING;

            UPDATE temporary.{$schemaLower}_{$modelLower}
            SET is_removed = TRUE, status = 'DRAFT'
            WHERE {$modelLower}_id = v_{$modelLower}_id AND session_id = p_session_id;
        END IF;

    -- 6. LOGIKA DRAFT / DEFAULT
    ELSE
        INSERT INTO temporary.{$schemaLower}_{$modelLower} (
            {$modelLower}_id,
            {$modelLower}_name,
            status,
            session_id,
            is_removed
        )
        VALUES (
            v_{$modelLower}_id,
            p_payload ->> '{$modelLower}_name',
            'DRAFT',
            p_session_id,
            v_is_removed
        )
        ON CONFLICT ({$modelLower}_id)
            DO UPDATE SET
                {$modelLower}_name = EXCLUDED.{$modelLower}_name,
                status            = EXCLUDED.status,
                is_removed        = EXCLUDED.is_removed;
    END IF;

EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION '%', SQLERRM;
END;
$$;
SQL;
            File::put($sqlFilePath, $sqlContent);

            if (!str_contains($content, 'use Spatie\Permission\Models\Permission;')) {
                $content = str_replace(
                    "use Illuminate\Support\Facades\Schema;",
                    "use Illuminate\Support\Facades\Schema;\nuse Illuminate\Support\Facades\DB;\nuse Spatie\Permission\Models\Permission;",
                    $content
                );
            }

            $content = preg_replace("/Schema::create\(['\"][^'\"]+['\"]/", "Schema::createWithTemp('{$fullTableName}'", $content);

            $content = preg_replace("/^\s*\\\$table->id\(\);\s*$/m", "", $content);
            $content = preg_replace("/^\s*\\\$table->timestamps\(\);\s*$/m", "", $content);

            $newColumns = "\n            \$table->string('{$modelLower}_id')->primary();" .
                "\n            \$table->string('{$modelLower}_name');\n" .
                "\n            \$table->baseColumn();";

            $content = preg_replace("/(function\s*\(Blueprint\s*\\\$table\)\s*\{)/", "$1$newColumns", $content);

            $historySchema = "\n        Schema::create('history.{$modelLower}_history', function (Blueprint \$table) {
            \$table->uuid('history_id')->primary();
            \$table->remoteForeign('executed_by', 'authentication.user', 'user_id');
            \$table->string('action');
            \$table->jsonb('old_data')->nullable();
            \$table->jsonb('new_data')->nullable();
            \$table->timestamp('executed_at')->useCurrent();
        });\n";

            $sqlInvoke = "\n        \$sql = file_get_contents(__DIR__ . '/sql/{$sqlFileName}');\n        DB::unprepared(\$sql);";

            $permissionCode = "\n        \$actions = ['lookup', 'view', 'add', 'edit', 'delete'];\n        foreach (\$actions as \$action) {\n            Permission::firstOrCreate(['name' => \"{$schemaLower}.{\$action}.{$modelLower}\", 'guard_name' => 'api']);\n        }";

            if (!str_contains($content, 'Permission::firstOrCreate')) {
                // Menambahkan historySchema tepat sebelum pemanggilan SQL invoke
                $content = str_replace("});\n    }", "});\n{$historySchema}{$sqlInvoke}\n{$permissionCode}\n    }", $content);
            }

            $downReplacement = "public function down(): void\n    {\n        DB::unprepared(\"DROP PROCEDURE IF EXISTS {$schemaLower}.procedure_action_{$modelLower}\");\n        Schema::dropIfExists('history.{$modelLower}_history');\n        Schema::dropIfExists('{$tempTableName}');\n        Schema::dropIfExists('{$fullTableName}');\n    }";
            $content = preg_replace("/public function down\(\): void\s*\{.*?Schema::dropIfExists\(.*?\);\s*\}/s", $downReplacement, $content);

            File::put($latestFile->getRealPath(), $content);

            $this->line("Fixed Migration: <info>{$latestFile->getFilename()}</info>");
            $this->line("SQL Procedure Generated: <info>{$sqlFileName}</info>");
        }
    }
}
