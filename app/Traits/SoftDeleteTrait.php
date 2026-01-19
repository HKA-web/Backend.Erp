<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait SoftDeleteTrait
{
    protected static function bootSoftDeleteTrait()
    {
        static::addGlobalScope('not_deleted', function (Builder $builder) {
            $builder->where(
                (new static)->getTable() . '.is_removed',
                false
            );
        });

        static::deleting(function ($model) {
            if ($model->forceDeleting ?? false) {
                return;
            }

            $model->is_removed = true;
            $model->save();

            return false;
        });
    }

    public static function withDeleted()
    {
        return (new static)->newQueryWithoutScope('not_deleted');
    }

    public static function onlyDeleted()
    {
        return static::withDeleted()->where('is_removed', true);
    }

    public function restore()
    {
        $this->is_removed = false;
        return $this->save();
    }

    public function forceDelete()
    {
        $this->forceDeleting = true;
        return parent::delete();
    }
}
