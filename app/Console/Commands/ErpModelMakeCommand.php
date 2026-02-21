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
            $modelLower = Str::lower($model);

            $content = str_replace("use Illuminate\Support\Facades\Schema;", "use Illuminate\Support\Facades\Schema;\nuse Spatie\Permission\Models\Permission;", $content);
            $content = preg_replace("/Schema::create\('([^']+)'/", "Schema::create('{$schema}.{$modelLower}'", $content);
            $content = preg_replace("/Schema::dropIfExists\('([^']+)'/", "Schema::dropIfExists('{$schema}.{$modelLower}'", $content);

            $content = str_replace("\$table->id();", "\$table->string('{$modelLower}_id')->primary();\n            \$table->string('{$modelLower}_name');", $content);
            $content = str_replace("\$table->timestamps();", "\$table->baseColumn();", $content);

            $permissionCode = "\n            \$actions = ['lookup', 'view', 'add', 'edit', 'delete'];\n            foreach (\$actions as \$action) {\n                Permission::firstOrCreate(['name' => \"{$schema}.{\$action}.{$modelLower}\", 'guard_name' => 'api']);\n            }";
            $content = str_replace("});\n    }", "});\n{$permissionCode}\n    }", $content);

            File::put($latestFile->getRealPath(), $content);
            $this->line("Fixed Migration: <info>{$latestFile->getFilename()}</info>");
        }
    }
}
