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

        $this->generateSchemaMigration($name, $lowerName);

        $this->info("ERP Module {$name} is ready!");
    }

    protected function generateSchemaMigration($name, $lowerName)
    {
        $pathKapital = base_path("Modules/{$name}/Database/Migrations");
        $pathKecil = base_path("Modules/{$name}/database/migrations");

        if (File::isDirectory($pathKecil)) {
            $path = $pathKecil;
        } else {
            $path = $pathKapital;
            if (! File::isDirectory($path)) {
                File::makeDirectory($path, 0755, true);
            }
        }

        $fileName = "0000_00_00_000000_create_{$lowerName}_schema.php";
        $fullPath = "{$path}/{$fileName}";

        $template = $this->getSchemaTemplate($lowerName);
        File::put($fullPath, $template);

        $this->line('Schema migration created in: <info>'.($path === $pathKecil ? 'database' : 'Database').'</info>');
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
}
