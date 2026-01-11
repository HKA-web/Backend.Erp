<?php

namespace App\Http\Resources;

use Illuminate\Support\Collection;

trait ExpandableResource
{
    protected function expandable(string $relation, string $resource, ?string $keyField = null)
    {
        if ($this->relationLoaded($relation)) {
            $rel = $this->{$relation};

            if (is_null($rel)) {
                return null;
            }

            if ($rel instanceof Collection) {
                return $resource::collection($rel);
            }

            return new $resource($rel);
        }

        if (is_null($keyField)) {
            return [];
        }

        return $this->{$keyField} ?? null;
    }
}
