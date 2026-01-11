<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class QueryHelper
{
    public static function getConnection(Request $request, ?Model $model = null): string
    {
        $connection = $request->query('connection');

        if ($connection) {
            return $connection;
        }

        if ($model) {
            return $model->getConnectionName();
        }

        return config('database.default');
    }

    public static function newQuery(Model $model, ?string $connection = null)
    {
        if ($connection) {
            $model->setConnection($connection);
        }

        return $model->newQuery();
    }

    public static function query(string $modelClass, Request $request)
    {
        $model = new $modelClass();
        $connection = self::getConnection($request, $model);

        return self::newQuery($model, $connection);
    }
}
