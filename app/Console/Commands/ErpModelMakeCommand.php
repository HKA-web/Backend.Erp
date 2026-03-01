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
        $this->generateFromStub('erp-stubs/controller_draft', base_path("Modules/{$module}/app/Http/Controllers/{$model}DraftController.php"), $vars);
        $this->generateFromStub('erp-stubs/requests', base_path("Modules/{$module}/app/Http/Requests/{$model}Request.php"), $vars);
        $this->generateFromStub('erp-stubs/model', base_path("Modules/{$module}/app/Models/{$model}.php"), $vars);
        $this->generateFromStub('erp-stubs/factory', base_path("Modules/{$module}/database/factories/{$model}Factory.php"), $vars);
        $this->injectRoute($module, $model);

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
            // File SQL sekarang menampung 3 procedure sekaligus
            $sqlFileName = "{$timestamp}_{$schemaLower}.procedures_{$modelLower}.sql";
            $sqlFilePath = $sqlDir . "/" . $sqlFileName;

            $pkName = "{$modelLower}_id";

            $sqlContent = <<<SQL
-- 1. PROCEDURE UPSERT DRAFT (Menggunakan temporary_id sebagai PK draf)
DROP PROCEDURE IF EXISTS {$schemaLower}.procedure_upsert_{$modelLower}_draft;
CREATE OR REPLACE PROCEDURE {$schemaLower}.procedure_upsert_{$modelLower}_draft(
    p_session_id UUID,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
DECLARE
    v_temp_id UUID := (p_payload ->> 'temporary_id')::UUID;
BEGIN
    -- Jika payload tidak bawa temporary_id, buat baru (Insert draf baru)
    IF v_temp_id IS NULL THEN
        v_temp_id := gen_random_uuid();
    END IF;

    INSERT INTO {$tempTableName} (
        temporary_id,
        session_id,
        master_id,
        temporary_option,
        {$pkName},
        {$modelLower}_name,
        is_removed
    ) VALUES (
        v_temp_id,
        p_session_id,
        p_payload ->> 'master_id',
        COALESCE(p_payload ->> 'temporary_option', 'I'),
        p_payload ->> '{$pkName}',
        p_payload ->> '{$modelLower}_name',
        COALESCE((p_payload ->> 'is_removed')::BOOLEAN, FALSE)
    ) ON CONFLICT (temporary_id) DO UPDATE SET
        {$modelLower}_name = EXCLUDED.{$modelLower}_name,
        is_removed = EXCLUDED.is_removed,
        temporary_option = EXCLUDED.temporary_option,
        updated_at = NOW();
END;
$$;

-- 2. PROCEDURE REVISE (Check-out dari Master ke Temporary)
DROP PROCEDURE IF EXISTS {$schemaLower}.procedure_revise_{$modelLower};
CREATE OR REPLACE PROCEDURE {$schemaLower}.procedure_revise_{$modelLower}(
    p_session_id UUID,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
DECLARE
    v_master_id TEXT := p_payload ->> '{$pkName}';
BEGIN
    -- Validasi Locking (Logical): Jangan biarkan user lain edit data yang sama di temporary
    IF EXISTS (SELECT 1 FROM {$tempTableName} WHERE master_id = v_master_id AND session_id <> p_session_id) THEN
        RAISE EXCEPTION 'Data ID % is currently being edited by another session.', v_master_id;
    END IF;

    INSERT INTO {$tempTableName} (
        temporary_id,
        session_id,
        master_id,
        temporary_option,
        {$pkName},
        {$modelLower}_name,
        is_removed
    )
    SELECT
        gen_random_uuid(),
        p_session_id,
        {$pkName},
        'U', -- Default 'U' (Update) karena narik dari master
        {$pkName},
        {$modelLower}_name,
        is_removed
    FROM {$fullTableName} WHERE {$pkName} = v_master_id
    ON CONFLICT (master_id, session_id) DO NOTHING;
END;
$$;

-- 3. PROCEDURE COMMIT (Finalisasi ke Master berdasarkan temporary_option)
DROP PROCEDURE IF EXISTS {$schemaLower}.procedure_commit_{$modelLower};
CREATE OR REPLACE PROCEDURE {$schemaLower}.procedure_commit_{$modelLower}(
    p_session_id UUID,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
DECLARE
    v_temp_id UUID := (p_payload ->> 'temporary_id')::UUID;
    v_rec RECORD;
    v_old_data JSONB;
    v_new_data JSONB;
BEGIN
    -- Ambil data dari temporary
    SELECT * INTO v_rec FROM {$tempTableName}
    WHERE temporary_id = v_temp_id AND session_id = p_session_id;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'Draft record not found.';
    END IF;

    -- A. Snapshot Data Lama (Jika Update/Delete)
    IF v_rec.master_id IS NOT NULL THEN
        SELECT to_jsonb(t) INTO v_old_data FROM {$fullTableName} t WHERE t.{$pkName} = v_rec.master_id;
    END IF;

    -- B. Eksekusi ke Master berdasarkan temporary_option
    IF v_rec.temporary_option = 'D' THEN
        DELETE FROM {$fullTableName} WHERE {$pkName} = v_rec.master_id;
    ELSE
        -- INSERT atau UPDATE
        INSERT INTO {$fullTableName} ({$pkName}, {$modelLower}_name, status, is_removed, created_at, updated_at)
        VALUES (v_rec.{$pkName}, v_rec.{$modelLower}_name, 'POSTED', v_rec.is_removed, NOW(), NOW())
        ON CONFLICT ({$pkName}) DO UPDATE SET
            {$modelLower}_name = EXCLUDED.{$modelLower}_name,
            is_removed = EXCLUDED.is_removed,
            status = 'POSTED',
            updated_at = NOW();
    END IF;

    -- C. History Logging
    SELECT to_jsonb(t) INTO v_new_data FROM {$fullTableName} t WHERE t.{$pkName} = v_rec.{$pkName};

    INSERT INTO history.{$schemaLower}_{$modelLower} (history_id, executed_by, action, old_data, new_data, executed_at)
    VALUES (
        gen_random_uuid(),
        (p_payload ->> 'executed_by')::UUID,
        CASE
            WHEN v_rec.temporary_option = 'D' THEN 'DELETE'
            WHEN v_old_data IS NULL THEN 'INSERT'
            ELSE 'UPDATE'
        END,
        v_old_data,
        v_new_data,
        NOW()
    );

    -- D. Cleanup Temporary
    DELETE FROM {$tempTableName} WHERE temporary_id = v_temp_id;
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

            // Ganti Schema::create standar dengan custom helper mu
            $content = preg_replace("/Schema::create\(['\"][^'\"]+['\"]/", "Schema::createWithTemp('{$fullTableName}'", $content);
            $content = preg_replace("/^\s*\\\$table->id\(\);\s*$/m", "", $content);
            $content = preg_replace("/^\s*\\\$table->timestamps\(\);\s*$/m", "", $content);

            $newColumns = "\n            \$table->string('{$modelLower}_id')->primary();" .
                "\n            \$table->string('{$modelLower}_name');\n" .
                "\n            \$table->baseColumn();";

            $content = preg_replace("/(function\s*\(Blueprint\s*\\\$table\)\s*\{)/", "$1$newColumns", $content);

            $historySchema = "\n        Schema::create('history.{$schemaLower}_{$modelLower}', function (Blueprint \$table) {
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
                $content = str_replace("});\n    }", "});\n{$historySchema}{$sqlInvoke}\n{$permissionCode}\n    }", $content);
            }

            $downReplacement = "public function down(): void\n    {\n        DB::unprepared(\"DROP PROCEDURE IF EXISTS {$schemaLower}.procedure_upsert_{$modelLower}_draft\");\n        DB::unprepared(\"DROP PROCEDURE IF EXISTS {$schemaLower}.procedure_revise_{$modelLower}\");\n        DB::unprepared(\"DROP PROCEDURE IF EXISTS {$schemaLower}.procedure_commit_{$modelLower}\");\n        Schema::dropIfExists('history.{$schemaLower}_{$modelLower}');\n        Schema::dropIfExists('{$tempTableName}');\n        Schema::dropIfExists('{$fullTableName}');\n    }";

            $content = preg_replace("/public function down\(\): void\s*\{.*?Schema::dropIfExists\(.*?\);\s*\}/s", $downReplacement, $content);

            File::put($latestFile->getRealPath(), $content);

            $this->line("Fixed Migration: <info>{$latestFile->getFilename()}</info>");
            $this->line("SQL Procedures Generated (Upsert, Revise, Commit): <info>{$sqlFileName}</info>");
        }
    }

    protected function injectRoute($module, $model)
    {
        $moduleDir = base_path("Modules/{$module}");
        $path = "{$moduleDir}/routes/api.php";
        if (!File::exists($path)) {
            $path = "{$moduleDir}/Routes/api.php";
        }

        $stubPath = base_path("stubs/erp-stubs/route.stub");

        if (!File::exists($stubPath)) {
            $this->error("Stub not found: {$stubPath}");
            return;
        }

        if (!File::exists($path)) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, "<?php\n\nuse Illuminate\Support\Facades\Route;\n");
        }

        $modelLower = Str::lower($model);
        $module_lower = Str::lower($module);

        $currentContent = File::get($path);
        if (str_contains($currentContent, "{$model}Controller")) {
            $this->warn("⚠️  Route for {$model} already exists in api.php. Skipping...");
            return;
        }

        $useNamespace = "use Modules\\{$module}\\Http\\Controllers\\{$model}Controller;\n" .
            "use Modules\\{$module}\\Http\\Controllers\\{$model}DraftController;";

        $stub = File::get($stubPath);
        $routeCode = str_replace(
            ['{{model}}', '{{model_lower}}', '{{model_plural_lower}}', '{{module}}'],
            [$model, $modelLower, $module, $module_lower],
            $stub
        );

        if (!str_contains($currentContent, "{$model}Controller")) {
            $currentContent = preg_replace('/<\?php/', "<?php\n\n{$useNamespace}", $currentContent);
        }

        $finalContent = rtrim($currentContent) . "\n\n" . trim($routeCode) . "\n";

        if (File::put($path, $finalContent)) {
            $this->line("Injected: <info>Routes into {$path}</info>");
        }
    }
}
