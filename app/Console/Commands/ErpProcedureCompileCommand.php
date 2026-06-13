<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ErpProcedureCompileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:procedure-compile';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually compile all SQL procedures for central database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Compiling Central SQL Procedures...');
        $this->compileCentralProcedures();
        $this->info('Central procedures compiled successfully.');
    }

    protected function compileCentralProcedures(): void
    {
        $modulesPath = base_path('Modules');
        if (!File::isDirectory($modulesPath)) {
            return;
        }

        $modules = File::directories($modulesPath);
        $executedCount = 0;

        foreach ($modules as $modulePath) {
            $centralSqlDir = $modulePath . '/database/migrations/sql';
            if (!File::isDirectory($centralSqlDir)) {
                continue;
            }

            $sqlFiles = glob($centralSqlDir . '/*.sql');
            if ($sqlFiles) {
                foreach ($sqlFiles as $file) {
                    $sql = File::get($file);
                    try {
                        DB::unprepared($sql);
                        $executedCount++;
                    } catch (\Exception $e) {
                        Log::error("Failed to compile central procedure from {$file}: " . $e->getMessage());
                        throw $e;
                    }
                }
            }
        }

        if ($executedCount > 0) {
            Log::info("Compiled {$executedCount} central SQL procedure(s).");
            $this->line("  > Compiled {$executedCount} central SQL procedure(s).");
        }
    }


}
