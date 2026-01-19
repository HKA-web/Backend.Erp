<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use App\Models\History;

trait HistoryTrait
{
    protected static function bootHistoryTrait()
    {
        static::created(function (Model $model) {
            static::storeCreateHistory($model);
        });

        static::updated(function (Model $model) {
            static::storeUpdateHistory($model);
        });

        static::deleted(function (Model $model) {
            static::storeDeleteHistory($model);
        });
    }

    protected static function storeCreateHistory(Model $model)
    {
        static::saveHistory(
            $model,
            'create',
            null,
            $model->toArray()
        );
    }

    protected static function storeUpdateHistory(Model $model)
    {
        static::saveHistory(
            $model,
            'update',
            $model->getOriginal(),
            $model->getChanges()
        );
    }

    protected static function storeDeleteHistory(Model $model)
    {
        static::saveHistory(
            $model,
            'delete',
            $model->toArray(),
            null
        );
    }

    protected static function saveHistory(
        Model $model,
        string $action,
        $old = null,
        $new = null
    ) {
        History::create([
            'table_name' => $model->getTable(),
            'record_id'  => (string) $model->getKey(),
            'action'     => $action,
            'old_data'   => $old ? json_encode($old) : null,
            'new_data'   => $new ? json_encode($new) : null,
            'user_id'    => Auth::id(),
            'ip_address' => Request::ip(),
        ]);
    }
}
