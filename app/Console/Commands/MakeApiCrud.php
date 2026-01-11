<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

class MakeApiCrud extends Command
{
    protected $signature = 'make:api-crud {name} {--no-migration : Do not generate migration (managed=false like Django)}';
    protected $description = 'Generate Model, Controller, Requests, Resource, and optional Migration';

    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        [$studly, $snake] = $this->normalize($this->argument('name'));
        $withMigration = ! $this->option('no-migration');

        $this->info('INFO  Generating API CRUD.');
        $this->newLine();

        $this->logStep("Model {$studly}", fn () => $this->makeModel($studly, $snake));
        $this->logStep("Controller {$studly}", fn () => $this->makeController($studly, $snake));
        $this->logStep("Requests {$studly}", fn () => $this->makeRequests($studly, $snake));
        $this->logStep("Resource {$studly}", fn () => $this->makeResource($studly, $snake));

        if ($withMigration) {
            $this->logStep("Migration create_{$snake}_table", fn () => $this->makeMigration($snake));
        } else {
            $this->logStep("Migration skipped (managed=false)", fn () => null);
        }

        return self::SUCCESS;
    }

    protected function normalize(string $name): array
    {
        $studly = Str::studly(str_replace(['-', '_'], ' ', $name));
        $snake  = Str::snake($studly);

        return [$studly, $snake];
    }

    protected function logStep(string $label, \Closure $callback): void
    {
        $start = microtime(true);

        try {
            $callback();
            $time = (microtime(true) - $start) * 1000;
            $this->printLikeMigration($label, $time, 'DONE');
        } catch (\Throwable $e) {
            $time = (microtime(true) - $start) * 1000;
            $this->printLikeMigration($label, $time, 'FAIL');
            throw $e;
        }
    }

    protected function printLikeMigration(string $label, float $ms, string $status): void
    {
        $width = 100;
        $time  = number_format($ms, 2) . 'ms';
        $left = $label . ' ';
        $dots = str_repeat('.', max(1, $width - strlen($left) - strlen($time) - strlen($status) - 4));
        $line = $left . $dots . " {$time} {$status}";

        if ($status === 'DONE') {
            $this->line("<info>{$line}</info>");
        } else {
            $this->line("<error>{$line}</error>");
        }
    }

    // ------------------- MODEL -------------------
    protected function makeModel(string $name, string $table): void
    {
        $path = app_path("Models/{$name}.php");
        if ($this->files->exists($path)) {
            $this->warn("Model {$name} already exists, skipped.");
            return;
        }

        $unmanagedComment = $this->option('no-migration')
            ? "    // NOTE: unmanaged model (no migration generated, like Django managed = false)\n\n"
            : '';

        $stub = <<<PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class {$name} extends Model
{
{$unmanagedComment}    use HasApiTokens, HasFactory, Notifiable;

    protected \$connection = 'pgsql';
    protected \$table = '{$table}';
    protected \$primaryKey = '{$table}_id';

    public \$incrementing = false;
    protected \$keyType = 'string';

    protected \$fillable = [
        '{$table}_id',
        '{$table}_name',
    ];
}
PHP;

        $this->files->put($path, $stub);
    }

    // ------------------- CONTROLLER -------------------
    protected function makeController(string $name, string $table): void
    {
        $path = app_path("Http/Controllers/V1/{$name}Controller.php");
        if ($this->files->exists($path)) {
            $this->warn("Controller already exists, skipped.");
            return;
        }

        $var = Str::camel($name);

        $stub = <<<PHP
<?php

namespace App\Http\Controllers\V1;

use App\Helpers\DebugHelper;
use App\Helpers\ExpandHelper;
use App\Helpers\QueryHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\\{$name}\Store{$name}Request;
use App\Http\Requests\\{$name}\Update{$name}Request;
use App\Http\Resources\\{$name}\\{$name}Resource;
use App\Models\\{$name};
use App\Traits\Paginatable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class {$name}Controller extends Controller
{
    use Paginatable;

    public function index(Request \$request)
    {
        try {
            \$model = new {$name}();
            \$connection = QueryHelper::getConnection(\$request, \$model);

            \$expandTree = ExpandHelper::parse(\$request->query('expand'));
            \$with = ExpandHelper::toWith(\$expandTree);

            \$filterExpr = \$this->getFilterExpression(\$request);

            \$baseQuery = QueryHelper::newQuery(\$model, \$connection);
            \$baseQuery = \$this->applyExpressionFilter(\$baseQuery, \$filterExpr);

            \$query = clone \$baseQuery;
            \$query = \$query->with(\$with);

            \$pagination = \$this->paginateQuery(\$query, \$request);

            return response()->json(array_merge([
                'connection' => Str::studly(\$connection),
                'status'     => Str::studly('success'),
                'message'    => 'List data retrieved successfully',
                'expand'     => \$expandTree,
            ], \$pagination, [
                'data' => {$name}Resource::collection(\$pagination['data']),
            ]));
        } catch (\Throwable \$e) {
            return response()->json([
                'connection' => QueryHelper::getConnection(\$request, new {$name}()),
                'status'     => Str::studly('error'),
                'message'    => config('app.debug') ? \$e->getMessage() : 'An error occurred while retrieving data.',
                'trace'      => config('app.debug') ? DebugHelper::formatTrace(\$e) : null,
            ], 500);
        }
    }

    public function show(Request \$request, \$id)
    {
        try {
            \$model = new {$name}();
            \$connection = QueryHelper::getConnection(\$request, \$model);

            \$expandTree = ExpandHelper::parse(\$request->query('expand'));
            \$with = ExpandHelper::toWith(\$expandTree);

            \$filterExpr = \$this->getFilterExpression(\$request);

            \$query = QueryHelper::newQuery(\$model, \$connection)->with(\$with);
            \$query = \$this->applyExpressionFilter(\$query, \$filterExpr);
            \$query->where('{$table}_id', \$id);

            \$data = \$query->firstOrFail();

            return response()->json([
                'connection' => Str::studly(\$connection),
                'status'     => Str::studly('success'),
                'message'    => 'Data retrieved successfully',
                'expand'     => \$expandTree,
                'data'       => new {$name}Resource(\$data),
            ]);
        } catch (\Throwable \$e) {
            return response()->json([
                'connection' => QueryHelper::getConnection(\$request, new {$name}()),
                'status'     => Str::studly('error'),
                'message'    => config('app.debug') ? \$e->getMessage() : 'An error occurred while retrieving data.',
                'trace'      => config('app.debug') ? DebugHelper::formatTrace(\$e) : null,
            ], 500);
        }
    }

    public function store(Store{$name}Request \$request)
    {
        try {
            \$model = new {$name}();
            \$connection = QueryHelper::getConnection(\$request, \$model);

            \$data = \$request->validated();
            \$item = \$model;
            if (\$connection) \$item->setConnection(\$connection);
            \$item->fill(\$data);
            \$item->save();

            return response()->json([
                'connection' => Str::studly(\$connection),
                'status'     => Str::studly('success'),
                'message'    => 'Data created successfully',
                'data'       => new {$name}Resource(\$item),
            ]);
        } catch (\Throwable \$e) {
            return response()->json([
                'connection' => QueryHelper::getConnection(\$request, new {$name}()),
                'status'     => Str::studly('error'),
                'message'    => config('app.debug') ? \$e->getMessage() : 'An error occurred while creating data.',
                'trace'      => config('app.debug') ? DebugHelper::formatTrace(\$e) : null,
            ], 500);
        }
    }

    public function update(Update{$name}Request \$request, \$id)
    {
        try {
            \$query = QueryHelper::query({$name}::class, \$request);
            \$item = \$query->findOrFail(\$id);

            \$data = \$request->validated();
            \$item->update(\$data);

            return response()->json([
                'connection' => QueryHelper::getConnection(\$request, new {$name}()),
                'status'     => Str::studly('success'),
                'message'    => 'Data updated successfully',
                'data'       => new {$name}Resource(\$item),
            ]);
        } catch (\Throwable \$e) {
            return response()->json([
                'connection' => QueryHelper::getConnection(\$request, new {$name}()),
                'status'     => Str::studly('error'),
                'message'    => config('app.debug') ? \$e->getMessage() : 'An error occurred while updating data.',
                'trace'      => config('app.debug') ? DebugHelper::formatTrace(\$e) : null,
            ], 500);
        }
    }

    public function destroy(Request \$request, \$id)
    {
        try {
            \$query = QueryHelper::query({$name}::class, \$request);
            \$item = \$query->findOrFail(\$id);
            \$item->delete();

            return response()->json([
                'connection' => QueryHelper::getConnection(\$request, new {$name}()),
                'status'     => Str::studly('success'),
                'message'    => 'Data deleted successfully',
            ]);
        } catch (\Throwable \$e) {
            return response()->json([
                'connection' => QueryHelper::getConnection(\$request, new {$name}()),
                'status'     => Str::studly('error'),
                'message'    => config('app.debug') ? \$e->getMessage() : 'An error occurred while deleting data.',
                'trace'      => config('app.debug') ? DebugHelper::formatTrace(\$e) : null,
            ], 500);
        }
    }
}
PHP;

        $this->files->ensureDirectoryExists(app_path('Http/Controllers/V1'));
        $this->files->put($path, $stub);
    }

    // ------------------- REQUESTS -------------------
    protected function makeRequests(string $name, string $table): void
    {
        $dir = app_path("Http/Requests/{$name}");
        $this->files->ensureDirectoryExists($dir);

        // Store
        $storePath = "{$dir}/Store{$name}Request.php";
        if (!$this->files->exists($storePath)) {
            $stub = <<<PHP
<?php

namespace App\Http\Requests\\{$name};

use App\Http\Requests\BaseFormRequest;

class Store{$name}Request extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            '{$table}_id'   => 'required|string|max:255',
            '{$table}_name' => 'required|string|max:255',
        ];
    }
}
PHP;
            $this->files->put($storePath, $stub);
        }

        // Update
        $updatePath = "{$dir}/Update{$name}Request.php";
        if (!$this->files->exists($updatePath)) {
            $stub = <<<PHP
<?php

namespace App\Http\Requests\\{$name};

use App\Http\Requests\BaseFormRequest;

class Update{$name}Request extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            '{$table}_name' => 'sometimes|required|string|max:255',
        ];
    }
}
PHP;
            $this->files->put($updatePath, $stub);
        }
    }

    // ------------------- RESOURCE -------------------
    protected function makeResource(string $name, string $table): void
    {
        $dir  = app_path("Http/Resources/{$name}");
        $path = "{$dir}/{$name}Resource.php";

        if ($this->files->exists($path)) {
            $this->warn("Resource already exists, skipped.");
            return;
        }

        $this->files->ensureDirectoryExists($dir);

        $stub = <<<PHP
<?php

namespace App\Http\Resources\\{$name};

use App\Http\Resources\ExpandableResource;
use Illuminate\Http\Resources\Json\JsonResource;

class {$name}Resource extends JsonResource
{
    use ExpandableResource;

    public function toArray(\$request)
    {
        return [
            '{$table}_id'   => \$this->{$table}_id,
            '{$table}_name' => \$this->{$table}_name,
        ];
    }
}
PHP;

        $this->files->put($path, $stub);
    }

    // ------------------- MIGRATION -------------------
    protected function makeMigration(string $table): void
    {
        $exists = collect(glob(database_path("migrations/*_create_{$table}_table.php")))->isNotEmpty();
        if ($exists) {
            $this->warn("Migration for table '{$table}' already exists, skipped.");
            return;
        }

        $timestamp = now()->format('Y_m_d_His');
        $fileName  = "{$timestamp}_create_{$table}_table.php";
        $path      = database_path("migrations/{$fileName}");

        $stub = <<<PHP
<?php

use Illuminate\\Database\\Migrations\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$table}', function (Blueprint \$table) {
            \$table->string('{$table}_id')->primary();
            \$table->string('{$table}_name');
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
};
PHP;

        $this->files->put($path, $stub);
    }
}
