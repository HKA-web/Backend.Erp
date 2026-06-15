<?php

namespace App\Console\Commands;

use App\Services\OpenApiGeneratorService;
use Illuminate\Console\Command;

class ErpApiCommand extends Command
{
    protected $signature = 'erp:api-docs 
                            {--module= : Generate docs for specific module}
                            {--all : Generate docs for all modules}';

    protected $description = 'Generate OpenAPI documentation for modules';

    protected OpenApiGeneratorService $generator;

    public function __construct(OpenApiGeneratorService $generator)
    {
        parent::__construct();
        $this->generator = $generator;
    }

    public function handle(): int
    {
        if ($this->option('all')) {
            return $this->generateForAllModules();
        }

        $module = $this->option('module');

        if ($module) {
            return $this->generateForModule($module);
        }

        $this->error('Please specify --module or --all option');
        return Command::FAILURE;
    }

    protected function generateForAllModules(): int
    {
        $this->info('Generating API documentation for all modules...');

        $results = $this->generator->saveForAllModules();

        foreach ($results as $module => $success) {
            if ($success) {
                $this->info("✓ Generated docs for module: {$module}");
            } else {
                $this->warn("✗ No docs generated for module: {$module}");
            }
        }

        $this->info('API documentation generation completed!');
        return Command::SUCCESS;
    }

    protected function generateForModule(string $module): int
    {
        $this->info("Generating API documentation for module: {$module}...");

        $success = $this->generator->saveForModule($module);

        if ($success) {
            $this->info("✓ Generated docs for module: {$module}");
            return Command::SUCCESS;
        }

        $this->warn("✗ No docs generated for module: {$module}");
        return Command::FAILURE;
    }
}
