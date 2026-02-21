<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BaseModel
{
    protected static function bootBaseModel()
    {
        static::addGlobalScope('activeOnly', function (Builder $builder) {
            $builder->where('is_removed', false);

        });
    }
}
