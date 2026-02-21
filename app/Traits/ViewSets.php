<?php

namespace App\Traits;

use App\Helpers\ExpandHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait ViewSets
{
    protected function erpResponse(Builder $query)
    {
        $this->applyFields($query);

        $this->applyExpand($query);

        if (request()->has('filter')) {
            $this->applyExtremeFilter($query, request()->input('filter'));
        }

        if (request()->has('sort')) {
            $this->applyDevExtremeSort($query, request()->input('sort'));
        }

        $totalCount = (clone $query)->count();

        $data = $query->takeSkip()->get();

        return response()->json([
            'totalCount' => $totalCount,
            'data'       => $data
        ], 200);
    }

    protected function applyExpand(Builder $query)
    {
        if (request()->has('expand')) {
            $tree = ExpandHelper::parse(request()->input('expand'));
            $with = ExpandHelper::toWith($tree);
            $query->with($with);
        }
    }

    public function applyExtremeFilter(Builder $query, $filter)
    {
        if (empty($filter)) return $query;

        if (is_string($filter)) {
            $filter = json_decode($filter, true);
        }

        return $this->buildQuery($query, $filter);
    }

    private function buildQuery(Builder $query, $filter)
    {
        if ($this->isLeaf($filter)) {
            return $this->applyLeaf($query, $filter);
        }

        return $query->where(function ($q) use ($filter) {
            $logic = 'and';

            foreach ($filter as $item) {
                if (is_array($item)) {
                    if ($logic === 'and') {
                        $q->where(fn($sub) => $this->buildQuery($sub, $item));
                    } else {
                        $q->orWhere(fn($sub) => $this->buildQuery($sub, $item));
                    }
                } else {
                    $item = strtolower($item);
                    if ($item === 'and' || $item === 'or') {
                        $logic = $item;
                    }
                }
            }
        });
    }

    private function isLeaf($filter)
    {
        return isset($filter[0]) && !is_array($filter[0]);
    }

    private function applyLeaf(Builder $query, $leaf)
    {
        $field = $leaf[0];
        $operator = $leaf[1] ?? '=';
        $value = $leaf[2] ?? null;

        switch ($operator) {
            case '=':  return ($value === null) ? $query->whereNull($field) : $query->where($field, '=', $value);
            case '<>': return ($value === null) ? $query->whereNotNull($field) : $query->where($field, '<>', $value);
            case '>':  return $query->where($field, '>', $value);
            case '>=': return $query->where($field, '>=', $value);
            case '<':  return $query->where($field, '<', $value);
            case '<=': return $query->where($field, '<=', $value);
            case 'contains':    return $query->where($field, 'LIKE', "%{$value}%");
            case 'notcontains': return $query->where($field, 'NOT LIKE', "%{$value}%");
            case 'startswith':  return $query->where($field, 'LIKE', "{$value}%");
            case 'endswith':    return $query->where($field, 'LIKE', "%{$value}");
            default: return $query->where($field, '=', $value);
        }
    }

    protected function applyExtremeSort(Builder $query, $sort)
    {
        $sorts = is_string($sort) ? json_decode($sort, true) : $sort;
        if (!is_array($sorts)) return;

        foreach ($sorts as $s) {
            $field = $s['selector'] ?? $s['field'] ?? null;
            $direction = ($s['desc'] ?? false) ? 'desc' : 'asc';
            if ($field) $query->orderBy($field, $direction);
        }
    }

    protected function applyFields(Builder $query)
    {
        if (request()->has('fields')) {
            $fieldsString = request()->input('fields');
            $fields = array_map('trim', explode(',', $fieldsString));

            $tableName = $query->getModel()->getTable();
            $realColumns = Schema::getColumnListing($tableName);

            $validFields = array_intersect($fields, $realColumns);

            $primaryKey = $query->getModel()->getKeyName();
            if (!in_array($primaryKey, $validFields)) {
                $validFields[] = $primaryKey;
            }

            if (request()->has('expand')) {
                $foreignKeys = array_filter($realColumns, fn($col) => str_ends_with($col, '_id'));
                foreach ($foreignKeys as $fk) {
                    if (!in_array($fk, $validFields)) {
                        $validFields[] = $fk;
                    }
                }
            }

            $query->select($validFields);
        }
    }
}
