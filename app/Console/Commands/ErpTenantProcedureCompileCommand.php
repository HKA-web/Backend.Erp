<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ErpTenantProcedureCompileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erp:tenant:procedure-compile {--tenant= : Compile only for specific tenant ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually compile all SQL procedures for tenant databases';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Compiling Tenant SQL Procedures...');
        
        $tenantId = $this->option('tenant');
        $tenants = $tenantId ? Tenant::where('id', $tenantId)->get() : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->info('No tenants found.');
            return;
        }

        foreach ($tenants as $tenant) {
            $this->info("Compiling procedures for tenant: {$tenant->id}");
            $tenant->run(function () use ($tenant) {
                $this->compileTenantProcedures($tenant->id);
            });
        }
        $this->info('All tenant procedures compiled successfully.');
    }

    protected function compileTenantProcedures($tenantId): void
    {
        $modulesPath = base_path('Modules');
        if (!File::isDirectory($modulesPath)) {
            return;
        }

        $modules = File::directories($modulesPath);
        $executedCount = 0;

        foreach ($modules as $modulePath) {
            $tenantSqlDir = $modulePath . '/database/migrations/tenant/sql';
            if (!File::isDirectory($tenantSqlDir)) {
                continue;
            }

            $sqlFiles = glob($tenantSqlDir . '/*.sql');
            if ($sqlFiles) {
                foreach ($sqlFiles as $file) {
                    $sql = File::get($file);
                    try {
                        DB::unprepared($sql);
                        $executedCount++;
                    } catch (\Exception $e) {
                        Log::error("Failed to compile procedure on tenant {$tenantId} from {$file}: " . $e->getMessage());
                        throw $e;
                    }
                }
            }
        }

        if ($executedCount > 0) {
            Log::info("Compiled {$executedCount} SQL procedure(s) for tenant {$tenantId}.");
            $this->line("  > Compiled {$executedCount} SQL procedure(s).");
        }
    }
}
