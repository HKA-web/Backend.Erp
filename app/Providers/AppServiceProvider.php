<?php

namespace App\Providers;

use App\Helpers\ExpandHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
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
            string $onDelete = 'set null'
        ) {
            $this->string($column)->nullable()->index();
            return $this->foreign($column)
                ->references($remoteKey)
                ->on($remoteTable)
                ->onDelete($onDelete);
        });

        Blueprint::macro('baseColumn', function () {
            $this->boolean('enable')->default(true);
            $this->boolean('is_removed')->default(false);
            $this->boolean('readonly')->default(false);
            $this->jsonb('properties')->nullable();
            $this->string('created_by')->nullable();
            $this->string('updated_by')->nullable();
            $this->timestamps();
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
    }
}
