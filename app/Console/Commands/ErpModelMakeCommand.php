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

        // Ambil file migrasi terbaru yang baru saja dibuat oleh command artisan
        $latestFile = collect(File::files($dir))->sortByDesc(fn($f) => $f->getMTime())->first();

        if ($latestFile) {
            $content = File::get($latestFile->getRealPath());

            // Standarisasi Penamaan
            $modelLower  = Str::lower($model);
            $schemaLower = Str::lower($schema);
            $fullTableName = "{$schemaLower}.{$modelLower}";
            $tempTableName = "temporary.{$schemaLower}_{$modelLower}";

            // --- 1. GENERATE FILE SQL PROCEDURE ---
            $sqlDir = $dir . "/sql";
            if (!File::isDirectory($sqlDir)) File::makeDirectory($sqlDir, 0755, true);

            $timestamp   = date('Y_m_d_His');
            $sqlFileName = "{$timestamp}_{$schemaLower}.push_{$modelLower}.sql";
            $sqlFilePath = $sqlDir . "/" . $sqlFileName;

            $sqlContent = <<<SQL
CREATE OR REPLACE PROCEDURE {$schemaLower}.push_{$modelLower}(
    p_session_id UUID,
    p_payload JSONB
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_status TEXT;
    v_{$modelLower}_id TEXT;
BEGIN
    -- Extract info dari JSONB
    v_status := COALESCE(p_payload->>'status', 'DRAFT');
    v_{$modelLower}_id := p_payload->>'{$modelLower}_id';

    IF v_status = 'POSTED' THEN
        -- 1. MASUK KE MASTER
        INSERT INTO {$schemaLower}.{$modelLower} (
            {$modelLower}_id,
            {$modelLower}_name,
            status,
            created_at
        )
        VALUES (
            v_{$modelLower}_id,
            p_payload->>'{$modelLower}_name',
            'POSTED',
            NOW()
        )
        ON CONFLICT ({$modelLower}_id)
        DO UPDATE SET
            {$modelLower}_name = EXCLUDED.{$modelLower}_name,
            status = EXCLUDED.status,
            updated_at = NOW();

        -- 2. BERSIHKAN DRAFT DI TEMPORARY
        DELETE FROM temporary.{$schemaLower}_{$modelLower}
        WHERE {$modelLower}_id = v_{$modelLower}_id
          AND session_id = p_session_id;
    ELSE
        -- 3. JIKA DRAFT, MASUK KE TEMPORARY
        INSERT INTO temporary.{$schemaLower}_{$modelLower} (
            {$modelLower}_id,
            {$modelLower}_name,
            status,
            session_id
        )
        VALUES (
            v_{$modelLower}_id,
            p_payload->>'{$modelLower}_name',
            'DRAFT',
            p_session_id
        )
        ON CONFLICT ({$modelLower}_id)
        DO UPDATE SET
            {$modelLower}_name = EXCLUDED.{$modelLower}_name,
            status = EXCLUDED.status;
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        RAISE EXCEPTION 'UPSERT_FAILED: %', SQLERRM;
END;
$$;
SQL;
            File::put($sqlFilePath, $sqlContent);

            // --- 2. INJECT NAMESPACE & FACADES KE MIGRASI PHP ---
            if (!str_contains($content, 'use Spatie\Permission\Models\Permission;')) {
                $content = str_replace(
                    "use Illuminate\Support\Facades\Schema;",
                    "use Illuminate\Support\Facades\Schema;\nuse Illuminate\Support\Facades\DB;\nuse Spatie\Permission\Models\Permission;",
                    $content
                );
            }

            // --- 3. REFACTOR SCHEMA CREATE (MASTER & TEMP) ---
            $content = preg_replace("/Schema::create\(['\"][^'\"]+['\"]/", "Schema::createWithTemp('{$fullTableName}'", $content);

            // --- 4. REFACTOR COLUMNS (Dinamis berdasarkan Model) ---
            $content = preg_replace("/^\s*\\\$table->id\(\);\s*$/m", "", $content);
            $content = preg_replace("/^\s*\\\$table->timestamps\(\);\s*$/m", "", $content);

            $newColumns = "\n            \$table->string('{$modelLower}_id')->primary();" .
                "\n            \$table->string('{$modelLower}_name');\n" .
                "\n            \$table->baseColumn();";

            $content = preg_replace("/(function\s*\(Blueprint\s*\\\$table\)\s*\{)/", "$1$newColumns", $content);

            // --- 5. INJECT PEMANGGILAN SQL & PERMISSION ---
            $sqlInvoke = "\n        \$sql = file_get_contents(__DIR__ . '/sql/{$sqlFileName}');\n        DB::unprepared(\$sql);";

            $permissionCode = "\n        \$actions = ['lookup', 'view', 'add', 'edit', 'delete'];\n        foreach (\$actions as \$action) {\n            Permission::firstOrCreate(['name' => \"{$schemaLower}.{\$action}.{$modelLower}\", 'guard_name' => 'api']);\n        }";

            if (!str_contains($content, 'Permission::firstOrCreate')) {
                // Kita letakkan setelah penutup Schema::create agar tabel sudah ada saat procedure dibuat
                $content = str_replace("});\n    }", "});\n{$sqlInvoke}\n{$permissionCode}\n    }", $content);
            }

            // --- 6. REFACTOR DOWN METHOD ---
            $downReplacement = "public function down(): void\n    {\n        DB::unprepared(\"DROP PROCEDURE IF EXISTS {$schemaLower}.push_{$modelLower}\");\n        Schema::dropIfExists('{$tempTableName}');\n        Schema::dropIfExists('{$fullTableName}');\n    }";
            $content = preg_replace("/public function down\(\): void\s*\{.*?Schema::dropIfExists\(.*?\);\s*\}/s", $downReplacement, $content);

            // Simpan Perubahan
            File::put($latestFile->getRealPath(), $content);

            $this->line("Fixed Migration: <info>{$latestFile->getFilename()}</info>");
            $this->line("SQL Procedure Generated: <info>{$sqlFileName}</info>");
        }
    }
}
