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

            $sqlContent = <<<SQL
-- 1. PROCEDURE UPSERT DRAFT (Untuk CRUD di Workspace/Sandbox)
DROP PROCEDURE IF EXISTS {$schemaLower}.procedure_upsert_{$modelLower}_draft;
CREATE OR REPLACE PROCEDURE {$schemaLower}.procedure_upsert_{$modelLower}_draft(
    p_session_id UUID,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
BEGIN
    INSERT INTO {$tempTableName} (
        {$modelLower}_id,
        {$modelLower}_name,
        status,
        session_id,
        is_removed
    ) VALUES (
        p_payload ->> '{$modelLower}_id',
        p_payload ->> '{$modelLower}_name',
        'DRAFT',
        p_session_id,
        COALESCE((p_payload ->> 'is_removed')::BOOLEAN, FALSE)
    ) ON CONFLICT ({$modelLower}_id) DO UPDATE SET
        {$modelLower}_name = EXCLUDED.{$modelLower}_name,
        is_removed = EXCLUDED.is_removed,
        status = 'DRAFT';
END;
$$;

-- 2. PROCEDURE REVISE (Tarik Master ke Temporary)
DROP PROCEDURE IF EXISTS {$schemaLower}.procedure_revise_{$modelLower};
CREATE OR REPLACE PROCEDURE {$schemaLower}.procedure_revise_{$modelLower}(
    p_session_id UUID,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
DECLARE
    v_id TEXT := p_payload ->> '{$modelLower}_id';
BEGIN
    -- Validasi Locking: Cek jika sudah ada draft milik session lain
    IF EXISTS (SELECT 1 FROM {$tempTableName} WHERE {$modelLower}_id = v_id AND session_id <> p_session_id) THEN
        RAISE EXCEPTION 'Data is being edited by another user.';
    END IF;

    INSERT INTO {$tempTableName} ({$modelLower}_id, {$modelLower}_name, status, session_id, is_removed)
    SELECT {$modelLower}_id, {$modelLower}_name, 'DRAFT', p_session_id, FALSE
    FROM {$fullTableName} WHERE {$modelLower}_id = v_id
    ON CONFLICT ({$modelLower}_id) DO NOTHING;
END;
$$;

-- 3. PROCEDURE COMMIT (Finalisasi ke Master + Audit)
DROP PROCEDURE IF EXISTS {$schemaLower}.procedure_commit_{$modelLower};
CREATE OR REPLACE PROCEDURE {$schemaLower}.procedure_commit_{$modelLower}(
    p_session_id UUID,
    p_payload JSONB
) LANGUAGE plpgsql AS $$
DECLARE
    v_id TEXT := p_payload ->> '{$modelLower}_id';
    v_old_data JSONB;
    v_new_data JSONB;
    v_user_id UUID := (p_payload ->> 'user_id')::UUID;
    v_is_removed BOOLEAN;
BEGIN
    -- Ambil flag is_removed dari temporary sebelum dihapus
    SELECT is_removed INTO v_is_removed FROM {$tempTableName} WHERE {$modelLower}_id = v_id AND session_id = p_session_id;

    -- A. Snapshot Data Lama
    SELECT to_jsonb(t) INTO v_old_data FROM {$fullTableName} t WHERE t.{$modelLower}_id = v_id;

    -- B. Move ke Master
    INSERT INTO {$fullTableName} ({$modelLower}_id, {$modelLower}_name, status, is_removed, created_at, updated_at)
    SELECT {$modelLower}_id, {$modelLower}_name, 'POSTED', is_removed, NOW(), NOW()
    FROM {$tempTableName} WHERE {$modelLower}_id = v_id AND session_id = p_session_id
    ON CONFLICT ({$modelLower}_id) DO UPDATE SET
        {$modelLower}_name = EXCLUDED.{$modelLower}_name,
        is_removed = EXCLUDED.is_removed,
        status = 'POSTED',
        updated_at = NOW();

    -- C. Snapshot Baru & History
    SELECT to_jsonb(t) INTO v_new_data FROM {$fullTableName} t WHERE t.{$modelLower}_id = v_id;

    INSERT INTO history.{$modelLower}_history (history_id, executed_by, action, old_data, new_data, executed_at)
    VALUES (
        gen_random_uuid(),
        v_user_id,
        CASE WHEN v_is_removed THEN 'DELETE' WHEN v_old_data IS NULL THEN 'INSERT' ELSE 'UPDATE' END,
        v_old_data,
        v_new_data,
        NOW()
    );

    -- D. Cleanup Temporary
    DELETE FROM {$tempTableName} WHERE {$modelLower}_id = v_id AND session_id = p_session_id;
END;
$$;
SQL;

            File::put($sqlFilePath, $sqlContent);

            // --- Laravel Migration File Modification ---

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

            // Inject Kolom Standar
            $newColumns = "\n            \$table->string('{$modelLower}_id')->primary();" .
                "\n            \$table->string('{$modelLower}_name');\n" .
                "\n            \$table->baseColumn();";

            $content = preg_replace("/(function\s*\(Blueprint\s*\\\$table\)\s*\{)/", "$1$newColumns", $content);

            // Schema History
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
                $content = str_replace("});\n    }", "});\n{$historySchema}{$sqlInvoke}\n{$permissionCode}\n    }", $content);
            }

            // Update Down Method untuk cleanup 3 procedure
            $downReplacement = "public function down(): void\n    {\n        DB::unprepared(\"DROP PROCEDURE IF EXISTS {$schemaLower}.procedure_upsert_{$modelLower}_draft\");\n        DB::unprepared(\"DROP PROCEDURE IF EXISTS {$schemaLower}.procedure_revise_{$modelLower}\");\n        DB::unprepared(\"DROP PROCEDURE IF EXISTS {$schemaLower}.procedure_commit_{$modelLower}\");\n        Schema::dropIfExists('history.{$modelLower}_history');\n        Schema::dropIfExists('{$tempTableName}');\n        Schema::dropIfExists('{$fullTableName}');\n    }";

            $content = preg_replace("/public function down\(\): void\s*\{.*?Schema::dropIfExists\(.*?\);\s*\}/s", $downReplacement, $content);

            File::put($latestFile->getRealPath(), $content);

            $this->line("Fixed Migration: <info>{$latestFile->getFilename()}</info>");
            $this->line("SQL Procedures Generated (Upsert, Revise, Commit): <info>{$sqlFileName}</info>");
        }
    }

    protected function injectRoute($module, $model)
    {
        // 1. Normalisasi Path: Laravel Modules biasanya pakai 'routes' (huruf kecil)
        // Kita cek keduanya agar tidak salah sasaran
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

        // 2. Buat file jika belum ada
        if (!File::exists($path)) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, "<?php\n\nuse Illuminate\Support\Facades\Route;\n");
        }

        $modelLower = Str::lower($model);
        $modelPlural = Str::plural($modelLower);

        // 3. Baca konten dan cek duplikasi secara cerdas
        $currentContent = File::get($path);
        if (str_contains($currentContent, "{$model}Controller")) {
            $this->warn("⚠️  Route for {$model} already exists in api.php. Skipping...");
            return;
        }

        // 4. Siapkan Use Statements (Sesuaikan namespace dengan folder 'app' mu)
        $useNamespace = "use Modules\\{$module}\\Http\\Controllers\\{$model}Controller;\n" .
            "use Modules\\{$module}\\Http\\Controllers\\{$model}DraftController;";

        // 5. Replace Placeholders di Stub
        $stub = File::get($stubPath);
        $routeCode = str_replace(
            ['{{model}}', '{{model_lower}}', '{{model_plural_lower}}', '{{module}}'],
            [$model, $modelLower, $modelPlural, $module],
            $stub
        );

        // 6. Masukkan Use Statement setelah tag <?php
        if (!str_contains($currentContent, "{$model}Controller")) {
            $currentContent = preg_replace('/<\?php/', "<?php\n\n{$useNamespace}", $currentContent);
        }

        // 7. Gabungkan: Pastikan ada spasi antar route agar rapi
        $finalContent = rtrim($currentContent) . "\n\n" . trim($routeCode) . "\n";

        if (File::put($path, $finalContent)) {
            $this->line("Injected: <info>Routes into {$path}</info>");
        }
    }
}
