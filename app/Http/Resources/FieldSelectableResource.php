<?php

namespace App\Http\Resources;

trait FieldSelectableResource
{
    protected function filterFields(array $data, $request)
    {
        $fields = $request->query('fields');

        if (!$fields) {
            return $data;
        }

        $wanted = array_map('trim', explode(',', $fields));

        return array_intersect_key($data, array_flip($wanted));
    }
}
