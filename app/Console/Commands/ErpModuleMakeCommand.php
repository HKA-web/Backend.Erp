<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ErpModuleMakeCommand extends Command
{
    protected $signature = 'erp:make-module {name}';

    protected $description = 'Create a new ERP module with PostgreSQL Schema & Permissions';

    public function handle()
    {
        $name = Str::studly($this->argument('name'));
        $lowerName = Str::lower($name);

        $this->info("Building ERP Module: {$name}...");

        Artisan::call("module:make {$name}");
        $this->line('Standard module structure created.');

        $this->cleanupDefaultFiles($name);
        $this->setupTenantSeeder($name);
        $this->generateSchemaMigration($name, $lowerName);

        $this->info("ERP Module {$name} is ready!");
    }

    protected function generateSchemaMigration($name, $lowerName)
    {
        $lowerCasePath = base_path("Modules/{$name}/database/migrations/tenant");
        $studlyCasePath = base_path("Modules/{$name}/Database/Migrations/tenant");

        if (File::isDirectory(base_path("Modules/{$name}/database/migrations"))) {
            $path = $lowerCasePath;
        } else {
            $path = $studlyCasePath;
        }

        if (! File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $fileName = "0000_00_00_000000_create_{$lowerName}_schema.php";
        $fullPath = "{$path}/{$fileName}";

        $template = $this->getSchemaTemplate($lowerName);
        File::put($fullPath, $template);

        $this->line('Schema migration created in: <info>'.($path === $lowerCasePath ? 'database' : 'Database').'</info>');
    }

    protected function getSchemaTemplate($lowerName)
    {
        return <<<PHP
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS {$lowerName}');
    }

    public function down(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS {$lowerName} CASCADE');
    }
};
PHP;
    }

    protected function cleanupDefaultFiles($name)
    {
        $lowerCaseControllerPath = base_path("Modules/{$name}/app/Http/Controllers/{$name}Controller.php");
        $studlyCaseControllerPath = base_path("Modules/{$name}/App/Http/Controllers/{$name}Controller.php");

        if (File::exists($lowerCaseControllerPath)) {
            File::delete($lowerCaseControllerPath);
        } elseif (File::exists($studlyCaseControllerPath)) {
            File::delete($studlyCaseControllerPath);
        }

        $lowerCaseRoutePath = base_path("Modules/{$name}/routes/api.php");
        $studlyCaseRoutePath = base_path("Modules/{$name}/Routes/api.php");
        $routePath = File::exists($lowerCaseRoutePath) ? $lowerCaseRoutePath : $studlyCaseRoutePath;

        if (File::exists($routePath)) {
            File::put($routePath, "<?php\n\nuse Illuminate\Support\Facades\Route;\n");
        }
    }

    protected function setupTenantSeeder($name)
    {
        $lowerCaseSeederPath = base_path("Modules/{$name}/database/seeders/TenantDatabaseSeeder.php");
        $studlyCaseSeederPath = base_path("Modules/{$name}/Database/Seeders/TenantDatabaseSeeder.php");
        $seederPath = File::isDirectory(base_path("Modules/{$name}/database/seeders")) ? $lowerCaseSeederPath : $studlyCaseSeederPath;

        $stubPath = base_path('stubs/erp-stubs/tenant_seeder_runner.stub');
        if (File::exists($stubPath)) {
            $content = File::get($stubPath);
            $content = str_replace('{{module}}', $name, $content);
            File::put($seederPath, $content);
        }
    }
}
