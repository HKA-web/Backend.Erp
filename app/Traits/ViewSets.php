<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

trait ViewSets
{
    /**
     * NOTE: Fungsi ini hanya bekerja untuk Eloquent (Master Data).
     * Untuk DB::table (Draft), logic ini akan dilewati otomatis.
     */
    protected function applyFieldsExpand($query)
    {
        // Pastikan ini adalah Eloquent Builder sebelum lanjut
        if (!$query instanceof EloquentBuilder) return;

        $fieldsParam = request()->input('fields');
        $expandParam = request()->input('expand');

        if (!$fieldsParam && !$expandParam) return;

        $fields = $fieldsParam ? array_map('trim', explode(',', $fieldsParam)) : [];
        $expands = $expandParam ? array_map('trim', explode(',', $expandParam)) : [];

        $fieldTree = [];
        foreach ($fields as $field) {
            $parts = explode('.', $field);
            $column = array_pop($parts);
            $path = implode('.', $parts);
            $fieldTree[$path][] = $column;
        }

        $mainColumns = $fieldTree[""] ?? [];
        if (!empty($mainColumns)) {
            $model = $query->getModel();
            $primaryKey = $model->getKeyName();
            if (!in_array($primaryKey, $mainColumns)) $mainColumns[] = $primaryKey;

            $tableName = $model->getTable();
            $realColumns = Schema::getColumnListing($tableName);

            foreach ($expands as $rel) {
                $firstPart = explode('.', $rel)[0];
                $fk = Str::snake($firstPart) . '_id';
                if (in_array($fk, $realColumns) && !in_array($fk, $mainColumns)) {
                    $mainColumns[] = $fk;
                }
            }

            $mainColumns = array_intersect($mainColumns, $realColumns);
            $query->select($mainColumns);
        }

        $withRelations = [];
        foreach ($expands as $relPath) {
            $withRelations[$relPath] = function ($q) use ($relPath, $fieldTree) {
                $model = $q->getModel();
                $tableName = $model->getTable();
                $realColumns = Schema::getColumnListing($tableName);

                if (isset($fieldTree[$relPath])) {
                    $cols = $fieldTree[$relPath];
                    $pk = $model->getKeyName();
                    if (!in_array($pk, $cols)) $cols[] = $pk;

                    foreach ($fieldTree as $path => $columns) {
                        if (str_starts_with($path, $relPath . '.')) {
                            $subRel = str_replace($relPath . '.', '', $path);
                            $fk = Str::snake($subRel) . '_id';
                            if (in_array($fk, $realColumns) && !in_array($fk, $cols)) {
                                $cols[] = $fk;
                            }
                        }
                    }

                    foreach ($realColumns as $col) {
                        if (str_ends_with($col, '_id') && !in_array($col, $cols)) {
                            $cols[] = $col;
                        }
                    }
                    $q->select(array_intersect($cols, $realColumns));
                }
            };
        }

        if (!empty($withRelations)) {
            $query->with($withRelations);
        }
    }

    /**
     * Menghapus type hint 'Builder' agar bisa menerima Query\Builder
     */
    public function applyFilter($query, $filter)
    {
        if (empty($filter)) return $query;
        if (is_string($filter)) $filter = json_decode($filter, true);
        return $this->buildQuery($query, $filter);
    }

    private function isLeaf($filter)
    {
        return isset($filter[0]) && !is_array($filter[0]);
    }

    private function applyLeaf($query, $leaf)
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

    private function buildQuery($query, $filter)
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
                    if ($item === 'and' || $item === 'or') $logic = $item;
                }
            }
        });
    }

    protected function applySort($query, $sort)
    {
        $sorts = is_string($sort) ? json_decode($sort, true) : $sort;
        if (!is_array($sorts)) return;

        foreach ($sorts as $s) {
            $field = $s['selector'] ?? $s['field'] ?? null;
            $direction = ($s['desc'] ?? false) ? 'desc' : 'asc';
            if ($field) $query->orderBy($field, $direction);
        }
    }

    protected function erpResponse($data = null, $message = 'Success')
    {
        // Support Eloquent (Master) & Query Builder (Temporary/Draft)
        if ($data instanceof EloquentBuilder || $data instanceof QueryBuilder) {
            $query = $data;

            $this->applyFieldsExpand($query);

            if (request()->has('filter')) {
                $this->applyFilter($query, request()->input('filter'));
            }

            if (request()->has('sort')) {
                $this->applySort($query, request()->input('sort'));
            }

            $totalCount = (clone $query)->count();

            // takeSkip() adalah custom method, pastikan tersedia di Macro atau Trait lain
            $results = $query->takeSkip()->get();

            return response()->json([
                'totalCount' => $totalCount,
                'data'       => $results,
            ], 200);
        }

        $response = ['message' => $message];
        if (!is_null($data)) $response['data'] = $data;

        return response()->json($response, 200);
    }
}
