<?php

namespace App\Providers;

use App\Helpers\ExpandHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        QueryBuilder::macro('takeSkip', function () {
            $limit = request()->input('take', 10);
            $offset = request()->input('skip', 0);

            /** @var QueryBuilder $this */
            return $this->limit($limit)->offset($offset);
        });

        EloquentBuilder::macro('takeSkip', function () {
            $limit = request()->input('take', 10);
            $offset = request()->input('skip', 0);

            /** @var EloquentBuilder $this */
            return $this->limit($limit)->offset($offset);
        });

        Blueprint::macro('remoteForeign', function (
            string $column,
            string $remoteTable,
            string $remoteKey,
            string $type = 'string', // Optional change on parameter with: integer, uuid, bigInteger, dll
            string $onDelete = 'cascade' // Optional change on parameter with: cascade, restrict, no action, set null
        ) {
            $this->{$type}($column)->nullable()->index();

            return $this->foreign($column)
                ->references($remoteKey)
                ->on($remoteTable)
                ->onDelete($onDelete);
        });

        Blueprint::macro('selfForeign', function (
            string $column,
            string $references = 'id',
            string $type = 'string',
            string $onDelete = 'set null'
        ) {
            $columnExists = collect($this->getColumns())->firstWhere('name', $column);

            if (! $columnExists) {
                $this->{$type}($column)->nullable()->index();
            }

            return $this->foreign($column)
                ->references($references)
                ->on($this->getTable())
                ->onDelete($onDelete);
        });

        Blueprint::macro('baseColumn', function () {
            $this->jsonb('properties')->nullable();
            $this->boolean('enable')->default(true);
            $this->boolean('readonly')->default(false);
            $this->boolean('is_removed')->default(false);
            $this->remoteForeign('created_by', 'authentication.user', 'user_id');
            $this->remoteForeign('updated_by', 'authentication.user', 'user_id');
            $this->timestamps();
            $this->string('status')->default('DRAFT');
        });

        EloquentBuilder::macro('filterSort', function () {
            $filter = request()->input('filter');
            $sort = request()->input('sort');

            return $this;
        });

        EloquentBuilder::macro('expand', function () {
            $expand = request()->input('expand');
            if ($expand) {
                $tree = ExpandHelper::parse($expand);
                $with = ExpandHelper::toWith($tree);

                /** @var EloquentBuilder $this */
                $model = $this->getModel();
                $validWith = [];

                foreach ((array) $with as $relation) {
                    $parts = explode('.', $relation);
                    $currentModel = $model;
                    $isValid = true;

                    foreach ($parts as $part) {
                        if (! method_exists($currentModel, $part)) {
                            $isValid = false;
                            break;
                        }

                        try {
                            $relationObj = $currentModel->$part();
                            if ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                                $currentModel = new ($relationObj->getRelated()::class);
                            } else {
                                $isValid = false;
                                break;
                            }
                        } catch (\Exception $e) {
                            $isValid = false;
                            break;
                        }
                    }

                    if ($isValid) {
                        $validWith[] = $relation;
                    }
                }

                return $this->with($validWith);
            }

            /** @var EloquentBuilder $this */
            return $this;
        });

        Schema::macro('createWithTemp', function ($table, \Closure $callback) {
            Schema::create($table, $callback);

            $tempSchema = 'temporary';
            $flatTableName = str_replace('.', '_', $table);
            $fullTempPath = "{$tempSchema}.{$flatTableName}";

            DB::statement("CREATE SCHEMA IF NOT EXISTS {$tempSchema}");
            DB::statement("DROP TABLE IF EXISTS {$fullTempPath}");

            DB::statement("
                CREATE TABLE {$fullTempPath} AS
                SELECT
                    NULL::uuid AS temporary_id,
                    NULL::uuid AS parent_temporary_id,
                    NULL::varchar AS master_id,
                    NULL::varchar AS session_id,
                    'I'::char(1) AS temporary_option,
                    m.*
                FROM {$table} m
                WHERE 1=0
            ");

            Schema::table($fullTempPath, function (Blueprint $table) use ($fullTempPath) {
                $table->uuid('temporary_id')->primary()->change();
                $table->uuid('parent_temporary_id')->nullable()->index()->change();
                $table->string('master_id')->nullable()->index()->change();
                $table->char('session_id')->index()->change();
                $table->char('temporary_option', 1)->default('U')->comment('I: Insert, U: Update, D: Delete')->change();

                $constraintName = 'uk_'.str_replace('.', '_', $fullTempPath).'_master_session';
                $table->unique(['master_id', 'session_id'], $constraintName);
            });
        });
    }
}
