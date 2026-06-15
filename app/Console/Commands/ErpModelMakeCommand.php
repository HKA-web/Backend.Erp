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

        $timestamp = date('Y_m_d_His');
        $schemaLower = Str::lower($module);
        $modelLower = Str::lower($model);
        $sqlFileName = "{$timestamp}_{$schemaLower}.procedures_{$modelLower}.sql";

        $vars = [
            '{{model}}' => $model,
            '{{module}}' => $module,
            '{{module_lower}}' => Str::lower($module),
            '{{model_lower}}' => $modelLower,
            '{{schema}}' => $schemaLower,
            '{{full_table_name}}' => "{$schemaLower}.{$modelLower}",
            '{{temp_table_name}}' => "temporary.{$schemaLower}_{$modelLower}",
            '{{pk_name}}' => "{$modelLower}_id",
            '{{sql_file_name}}' => $sqlFileName,
        ];

        $this->info("🚀 Generating ERP Components for {$model}...");

        $this->generateFromStub('erp-stubs/controller', base_path("Modules/{$module}/app/Http/Controllers/{$model}Controller.php"), $vars);
        $this->generateFromStub('erp-stubs/controller_draft', base_path("Modules/{$module}/app/Http/Controllers/{$model}DraftController.php"), $vars);
        $this->generateFromStub('erp-stubs/requests', base_path("Modules/{$module}/app/Http/Requests/{$model}Request.php"), $vars);
        $this->generateFromStub('erp-stubs/model', base_path("Modules/{$module}/app/Models/{$model}.php"), $vars);
        $factoryBasePath = base_path("Modules/{$module}/database/factories/Tenant");
        if (! File::isDirectory($factoryBasePath)) {
            $factoryBasePath = base_path("Modules/{$module}/Database/Factories/Tenant");
        }
        $this->generateFromStub('erp-stubs/factory', "{$factoryBasePath}/{$model}Factory.php", $vars);
        
        $this->injectRoute($module, $model);

        $this->generateMigrationFiles($module, $model, $vars, $timestamp, $sqlFileName);

        $this->info('✅ Success! Files created exactly as requested.');
    }

    protected function generateFromStub($stubName, $dest, $vars)
    {
        $stubPath = base_path("stubs/{$stubName}.stub");

        if (! File::exists($stubPath)) {
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

    protected function generateMigrationFiles($module, $model, $vars, $timestamp, $sqlFileName)
    {
        $lowerCaseBasePath = base_path("Modules/{$module}/database/migrations");

        if (File::isDirectory($lowerCaseBasePath)) {
            $dir = $lowerCaseBasePath . '/Tenant';
        } else {
            $dir = base_path("Modules/{$module}/Database/Migrations/Tenant");
        }

        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $migrationFileName = "{$timestamp}_create_" . Str::snake(Str::plural($model)) . "_table.php";
        $migrationFilePath = "{$dir}/{$migrationFileName}";

        $sqlDir = "{$dir}/sql";
        if (! File::isDirectory($sqlDir)) {
            File::makeDirectory($sqlDir, 0755, true);
        }
        $sqlFilePath = "{$sqlDir}/{$sqlFileName}";

        $this->generateFromStub('erp-stubs/migration', $migrationFilePath, $vars);
        $this->generateFromStub('erp-stubs/sql_procedures', $sqlFilePath, $vars);
    }

    protected function injectRoute($module, $model)
    {
        $moduleDir = base_path("Modules/{$module}");
        $path = "{$moduleDir}/routes/api.php";
        if (! File::exists($path)) {
            $path = "{$moduleDir}/Routes/api.php";
        }

        $stubPath = base_path('stubs/erp-stubs/route.stub');

        if (! File::exists($stubPath)) {
            $this->error("Stub not found: {$stubPath}");

            return;
        }

        if (! File::exists($path)) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, "<?php\n\nuse Illuminate\Support\Facades\Route;\n");
        }

        $model_lower = Str::lower($model);
        $module_lower = Str::lower($module);

        $currentContent = File::get($path);
        if (str_contains($currentContent, "{$model}Controller")) {
            $this->warn("⚠️  Route for {$model} already exists in api.php. Skipping...");

            return;
        }

        $useNamespace = "use Modules\\{$module}\\Http\\Controllers\\{$model}Controller;\n".
            "use Modules\\{$module}\\Http\\Controllers\\{$model}DraftController;";

        $stub = File::get($stubPath);
        $routeCode = str_replace(
            ['{{model}}', '{{model_lower}}', '{{module}}', '{{module_lower}}'],
            [$model, $model_lower, $module, $module_lower],
            $stub
        );

        if (! str_contains($currentContent, "{$model}Controller")) {
            $currentContent = preg_replace('/<\?php/', "<?php\n\n{$useNamespace}", $currentContent);
        }

        $finalContent = rtrim($currentContent)."\n\n".trim($routeCode)."\n";

        if (File::put($path, $finalContent)) {
            $this->line("Injected: <info>Routes into {$path}</info>");
        }
    }

}
