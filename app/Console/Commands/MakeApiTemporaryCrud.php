<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;

class MakeApiTemporaryCrud extends Command
{
    protected $signature = 'make:api-temporary-crud
        {name : Master model name, example: Order}';

    protected $description = 'Generate FULL REST API for temporary table (mirror master)';

    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
        $this->original_name = '';
    }

    public function handle(): int
    {
        [$studly, $snake] = $this->normalize($this->argument('name'));

        $masterModel            = $studly;
        $masterTable            = $snake;
        $temporaryModel         = "{$studly}Temporary";
        $temporaryTable         = "{$snake}_temporary";
        $this->original_name    = Str::lower($masterModel);

        $this->info('INFO  Generating TEMPORARY REST API.');
        $this->newLine();

        $columns = $this->getTableColumns($masterTable);

        $this->logStep("Model {$temporaryModel}", fn() =>
        $this->makeModel($temporaryModel, $temporaryTable, $columns, $masterModel)
        );

        $this->logStep("Controller {$temporaryModel}", fn() =>
        $this->makeController($temporaryModel, $temporaryTable, $masterModel)
        );

        $this->logStep("Requests {$temporaryModel}", fn() =>
        $this->makeRequests($temporaryModel, $temporaryTable, $columns)
        );

        $this->logStep("Resource {$temporaryModel}", fn() =>
        $this->makeResource($temporaryModel, $temporaryTable, $columns)
        );

        $this->logStep("Migration create_{$temporaryTable}_table", fn() =>
        $this->makeMigration($temporaryTable, $columns, $masterTable)
        );

        return self::SUCCESS;
    }

    protected function normalize(string $name): array
    {
        $studly = Str::studly(str_replace(['-', '_'], ' ', $name));
        $snake  = Str::snake($studly);
        return [$studly, $snake];
    }

    protected function getTableColumns(string $table): array
    {
        $connection = config('database.default');

        $cols = DB::connection($connection)
            ->getSchemaBuilder()
            ->getColumnListing($table);

        // buang kolom default yang tidak kita mirror
        return array_values(array_diff($cols, [
            'created_at', 'updated_at',
        ]));
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

        $this->line($status === 'DONE' ? "<info>{$line}</info>" : "<error>{$line}</error>");
    }

    protected function makeModel(string $name, string $table, array $columns, string $masterModel): void
    {
        $path = app_path("Models/{$name}.php");
        if ($this->files->exists($path)) return;

        $fillable = array_merge($columns, ['session_id']);

        $fillableStr = implode("',\n        '", $fillable);

        $stub = <<<PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class {$name} extends Model
{
    use HasFactory;

    protected \$connection = 'pgsql';
    protected \$table = '{$table}';
    protected \$primaryKey = 'temporary_id';
    public \$incrementing = true;
    protected \$keyType = 'int';

    protected \$fillable = [
        '{$fillableStr}',
    ];

    // Uncoment this code if with relation
    //public function {$this->original_name}()
    //{
    //   return \$this->belongsTo({$masterModel}::class, '{$this->original_name}_id', '{$this->original_name}_id');
    //}
}
PHP;

        $this->files->put($path, $stub);
    }

    protected function makeController(string $name, string $table, string $masterModel): void
    {
        $path = app_path("Http/Controllers/V1/{$name}Controller.php");
        if ($this->files->exists($path)) return;

        $temporaryRequestNs = "App\\Http\\Requests\\{$name}";
        $temporaryResourceNs = "App\\Http\\Resources\\{$name}";
        $masterResourceNs = "App\\Http\\Resources\\{$masterModel}";
        $originalId = "{$this->original_name}_id";

        $stub = <<<PHP
<?php

namespace App\Http\Controllers\V1;

use App\Helpers\DebugHelper;
use App\Helpers\ExpandHelper;
use App\Helpers\QueryHelper;
use App\Http\Controllers\Controller;
use {$temporaryRequestNs}\\Store{$name}Request;
use {$temporaryRequestNs}\\Update{$name}Request;
use {$temporaryResourceNs}\\{$name}Resource;
use App\Models\\{$name};
use App\Models\\{$masterModel};
use App\Traits\TransactionTrait;
use App\Traits\PaginateTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class {$name}Controller extends Controller
{
    use PaginateTrait, TransactionTrait;

    public function index(Request \$request)
    {
        try {
            \$model = new {$name}();
            \$connection = QueryHelper::getConnection(\$request, \$model);
            \$expandTree = ExpandHelper::parse(\$request->query('expand'));
            \$with = ExpandHelper::toWith(\$expandTree);
            \$sessionId = \$request->query('session_id');

            \$filterExpr = \$this->getFilterExpression(\$request);

            \$baseQuery = QueryHelper::newQuery(\$model, \$connection);
            \$baseQuery = \$this->applyExpressionFilter(\$baseQuery, \$filterExpr);

            \$query = clone \$baseQuery;
            \$query = \$query->with(\$with);

            if (\$sessionId) {
                \$query->where('session_id', \$sessionId);
            }

            \$pagination = \$this->paginateQuery(\$query, \$request);

            return response()->json(array_merge([
                'connection' => Str::studly(\$connection),
                'status'     => 'success',
                'message'    => 'List data retrieved successfully',
                'expand'     => \$expandTree,
            ], \$pagination, [
                'data' => {$name}Resource::collection(\$pagination['data']),
            ]));
        } catch (\\Throwable \$e) {
            return response()->json([
                'connection' => QueryHelper::getConnection(\$request, new {$name}()),
                'status'     => 'error',
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

            \$baseQuery = QueryHelper::newQuery(\$model, \$connection)->with(\$with);
            \$baseQuery = \$this->applyExpressionFilter(\$baseQuery, \$filterExpr);

            \$query = clone \$baseQuery;
            \$query->where('temporary_id', \$id);

            \$data = \$query->firstOrFail();

            return response()->json(array_merge([
                'connection' => Str::studly(\$connection),
                'status'     => 'success',
                'message'    => 'List data retrieved successfully',
                'expand'     => \$expandTree,
                'data' => new {$name}Resource(\$data),
            ]));
        } catch (\\Throwable \$e) {
            return response()->json([
                'connection' => QueryHelper::getConnection(\$request, new {$name}()),
                'status'     => 'error',
                'message'    => config('app.debug') ? \$e->getMessage() : 'An error occurred while retrieving data.',
                'trace'      => config('app.debug') ? DebugHelper::formatTrace(\$e) : null,
            ], 500);
        }
    }

    public function store(Store{$name}Request \$request)
    {
        return \$this->executeTransaction(function () use (\$request) {
            \$model = new {$name}();
            \$connection = QueryHelper::getConnection(\$request, \$model);

            if (\$connection) \$model->setConnection(\$connection);

            \$model->fill(\$request->validated());
            \$model->save();

            return new {$name}Resource(\$model);
        }, 'Data created successfully');
    }

    public function update(Update{$name}Request \$request, \$id)
    {
        return \$this->executeTransaction(function () use (\$request, \$id) {
            \$query = QueryHelper::query({$name}::class, \$request);
            \$item  = \$query->findOrFail(\$id);

            \$item->update(\$request->validated());

            return new {$name}Resource(\$item);
        }, 'Data updated successfully');
    }

    public function destroy(Request \$request, \$id)
    {
        return \$this->executeTransaction(function () use (\$request, \$id) {
            \$query = QueryHelper::query({$name}::class, \$request);
            \$item  = \$query->findOrFail(\$id);
            \$item->delete();

            return null;
        }, 'Data deleted successfully');
    }

    public function commit(Request \$request)
    {
        return \$this->executeTransaction(function () use (\$request) {

            \$sessionId = \$request->input('session_id');

            \$rows = {$name}::where('session_id', \$sessionId)->get();

            foreach (\$rows as \$row) {
                \$data = \$row->toArray();

                unset(
                    \$data['temporary_id'],
                    \$data['session_id'],
                    \$data['created_at'],
                    \$data['updated_at']
                );

                {$masterModel}::updateOrCreate(
                    ['{$originalId}' => \$data['{$originalId}']],
                    \$data
                );
            }

            {$name}::where('session_id', \$sessionId)->delete();

        }, 'Transaction committed successfully');
    }
}
PHP;

        $this->files->ensureDirectoryExists(app_path('Http/Controllers/V1'));
        $this->files->put($path, $stub);
    }

    protected function makeRequests(string $name, string $table, array $columns): void
    {
        $dir = app_path("Http/Requests/{$name}");
        $this->files->ensureDirectoryExists($dir);

        $rules = [];
        foreach ($columns as $col) {
            $rules[] = "'{$col}' => 'nullable'";
        }
        $rulesStr = implode(",\n            ", $rules);

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
            {$rulesStr},
            'session_id' => 'nullable|string',
        ];
    }
}
PHP;
            $this->files->put($storePath, $stub);
        }

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
            {$rulesStr},
        ];
    }
}
PHP;
            $this->files->put($updatePath, $stub);
        }
    }

    protected function makeResource(string $name, string $table, array $columns): void
    {
        $dir = app_path("Http/Resources/{$name}");
        $this->files->ensureDirectoryExists($dir);

        $fields = array_merge(['temporary_id'], $columns, ['session_id']);

        $map = [];
        foreach ($fields as $f) {
            $map[] = "'{$f}' => \$this->{$f}";
        }

        $mapStr = implode(",\n            ", $map);

        $stub = <<<PHP
<?php

namespace App\Http\Resources\\{$name};

use Illuminate\Http\Resources\Json\JsonResource;

class {$name}Resource extends JsonResource
{
    public function toArray(\$request)
    {
        return [
            {$mapStr},
        ];
    }
}
PHP;

        $this->files->put("{$dir}/{$name}Resource.php", $stub);
    }

    protected function makeMigration(string $table, array $columns, string $masterTable): void
    {
        $exists = collect(glob(database_path("migrations/*_create_{$table}_table.php")))->isNotEmpty();
        if ($exists) return;

        $timestamp = now()->format('Y_m_d_His');
        $fileName  = "{$timestamp}_create_{$table}_table.php";
        $path      = database_path("migrations/{$fileName}");

        $fields = [];
        foreach ($columns as $col) {
            $fields[] = "\$table->string('{$col}')->nullable();";
        }
        $fieldsStr = implode("\n            ", $fields);

        $stub = <<<PHP
<?php

use Illuminate\\Database\\Migrations\Migration;
use Illuminate\\Database\\Schema\Blueprint;
use Illuminate\\Support\\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$table}', function (Blueprint \$table) {
            \$table->bigIncrements('temporary_id');
            {$fieldsStr}
            \$table->string('session_id')->index();
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
