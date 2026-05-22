<?php

namespace App\Console\Commands;

use App\Services\OpenApiGeneratorService;
use Illuminate\Console\Command;

class GenerateApiDocs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:docs:generate 
                            {--module= : Generate docs for specific module}
                            {--all : Generate docs for all modules}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate OpenAPI documentation for modules';

    /**
     * The OpenAPI generator service.
     *
     * @var OpenApiGeneratorService
     */
    protected OpenApiGeneratorService $generator;

    /**
     * Create a new command instance.
     *
     * @param OpenApiGeneratorService $generator
     * @return void
     */
    public function __construct(OpenApiGeneratorService $generator)
    {
        parent::__construct();
        $this->generator = $generator;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
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

    /**
     * Generate documentation for all modules.
     *
     * @return int
     */
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

    /**
     * Generate documentation for a specific module.
     *
     * @param string $module
     * @return int
     */
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
