<?php

namespace App\Providers;

use App\Helpers\ExpandHelper;
use Illuminate\Database\Eloquent\Builder;
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
        Blueprint::macro('remoteForeign', function (
            string $column,
            string $remoteTable,
            string $remoteKey,
            string $type = 'string', // Optional change on parameter with: integer, uuid, bigInteger, dll
            string $onDelete = 'set null' // Optional change on parameter with: cascade, restrict, no action
        ) {
            $this->{$type}($column)->nullable()->index();

            return $this->foreign($column)
                ->references($remoteKey)
                ->on($remoteTable)
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

        Builder::macro('takeSkip', function () {
            if (!request()->has('take')) {
                return $this;
            }

            $take = (int) request('take', 10);
            $skip = (int) request('skip', 0);

            $take = $take < 1 ? 10 : $take;
            $skip = $skip < 0 ? 0 : $skip;

            return $this->offset($skip)->limit($take);
        });

        Builder::macro('filterSort', function () {
            $filter = request()->input('filter');
            $sort = request()->input('sort');
            return $this;
        });

        Builder::macro('expand', function () {
            $expand = request()->input('expand');
            if ($expand) {
                $tree = ExpandHelper::parse($expand);
                $with = ExpandHelper::toWith($tree);
                return $this->with($with);
            }
            return $this;
        });

        Schema::macro('createWithTemp', function ($table, \Closure $callback) {
            Schema::create($table, $callback);

            $tempSchema = 'temporary';
            $flatTableName = str_replace('.', '_', $table);
            $fullTempPath = "{$tempSchema}.{$flatTableName}";

            DB::statement("CREATE SCHEMA IF NOT EXISTS {$tempSchema}");

            DB::statement("DROP TABLE IF EXISTS {$fullTempPath}");
            DB::statement("CREATE TABLE {$fullTempPath} (LIKE {$table} INCLUDING ALL)");

            Schema::table($fullTempPath, function (Blueprint $table) {
                $table->uuid('session_id')->nullable()->index();
            });
        });
    }
}
