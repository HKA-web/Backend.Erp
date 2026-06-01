<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Services\CacheService;

trait ViewSets
{
    protected function applyFieldsExpand($query)
    {
        if (! $query instanceof EloquentBuilder) {
            return;
        }

        $fieldsParam = Request::input('fields');
        $expandParam = Request::input('expand');

        if (! $fieldsParam && ! $expandParam) {
            return;
        }

        $fields = $fieldsParam ? array_map('trim', explode(',', $fieldsParam)) : [];
        $expands = $expandParam ? array_map('trim', explode(',', $expandParam)) : [];

        $fieldTree = [];
        foreach ($fields as $field) {
            $parts = explode('.', $field);
            $column = array_pop($parts);
            $path = implode('.', $parts);
            $fieldTree[$path][] = $column;
        }

        $mainColumns = $fieldTree[''] ?? [];
        if (! empty($mainColumns)) {
            $model = $query->getModel();
            $primaryKey = $model->getKeyName();
            if (! in_array($primaryKey, $mainColumns)) {
                $mainColumns[] = $primaryKey;
            }

            $tableName = $model->getTable();
            $realColumns = Schema::getColumnListing($tableName);

            foreach ($expands as $rel) {
                $firstPart = explode('.', $rel)[0];
                $fk = Str::snake($firstPart).'_id';
                if (in_array($fk, $realColumns) && ! in_array($fk, $mainColumns)) {
                    $mainColumns[] = $fk;
                }
            }

            $mainColumns = array_intersect($mainColumns, $realColumns);
            $query->select($mainColumns);
        }

        $withRelations = [];
        foreach ($expands as $relPath) {
            // Check all levels of the nested relationship
            $parts = explode('.', $relPath);
            $currentModel = $query->getModel();
            $isValid = true;

            foreach ($parts as $part) {
                if (! method_exists($currentModel, $part)) {
                    $isValid = false;
                    break;
                }

                try {
                    $relationObj = $currentModel->$part();
                    if ($relationObj instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                        $currentModel = new ($relationObj->getRelated()::class);
                    } else {
                        $isValid = false;
                        break;
                    }
                } catch (\Exception $e) {
                    $isValid = false;
                    break;
                }
            }

            if ($isValid) {
                $withRelations[$relPath] = function ($q) use ($relPath, $fieldTree) {
                    $model = $q->getModel();
                    $tableName = $model->getTable();
                    $realColumns = Schema::getColumnListing($tableName);

                    if (isset($fieldTree[$relPath])) {
                        $cols = $fieldTree[$relPath];
                        $pk = $model->getKeyName();
                        if (! in_array($pk, $cols)) {
                            $cols[] = $pk;
                        }

                        foreach ($fieldTree as $path => $columns) {
                            if (str_starts_with($path, $relPath.'.')) {
                                $subRel = str_replace($relPath.'.', '', $path);
                                $fk = Str::snake($subRel).'_id';
                                if (in_array($fk, $realColumns) && ! in_array($fk, $cols)) {
                                    $cols[] = $fk;
                                }
                            }
                        }

                        foreach ($realColumns as $col) {
                            if (str_ends_with($col, '_id') && ! in_array($col, $cols)) {
                                $cols[] = $col;
                            }
                        }
                        $q->select(array_intersect($cols, $realColumns));
                    }
                };
            }
        }

        if (! empty($withRelations)) {
            $query->with($withRelations);
        }
    }

    public function applyFilter($query, $filter)
    {
        if (empty($filter)) {
            return $query;
        }
        if (is_string($filter)) {
            $filter = json_decode($filter, true);
        }

        return $this->buildQuery($query, $filter);
    }

    private function isLeaf($filter)
    {
        return isset($filter[0]) && ! is_array($filter[0]);
    }

    private function applyLeaf($query, $leaf)
    {
        $field = $leaf[0];
        $operator = $leaf[1] ?? '=';
        $value = $leaf[2] ?? null;

        if (str_contains($field, '.')) {
            $parts = explode('.', $field);
            if (in_array($parts[0], ['created_at', 'updated_at'])) {
                if (($parts[1] ?? '') === 'date') {
                    return $query->whereDate($parts[0], $operator, $value);
                }
                $field = $parts[0];
            } else {
                return $query->whereHas($parts[0], function ($q) use ($field, $operator, $value) {
                    $nestedField = explode('.', $field, 2)[1];
                    $this->applyLeaf($q, [$nestedField, $operator, $value]);
                });
            }
        }

        switch ($operator) {
            case '=':
                return ($value === null) ? $query->whereNull($field) : $query->where($field, '=', $value);
            case '<>':
                return ($value === null) ? $query->whereNotNull($field) : $query->where($field, '<>', $value);
            case '>':
            case '>=':
            case '<':
            case '<=':
                return $query->where($field, $operator, $value);
            case 'contains':
                return $query->where($field, 'LIKE', "%{$value}%");
            case 'icontains':
                return $query->where($field, 'ILIKE', "%{$value}%");
            case 'notcontains':
                return $query->where($field, 'NOT LIKE', "%{$value}%");
            case 'startswith':
                return $query->where($field, 'LIKE', "{$value}%");
            case 'endswith':
                return $query->where($field, 'LIKE', "%{$value}");
            default:
                return $query->where($field, '=', $value);
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
                        $q->where(fn ($sub) => $this->buildQuery($sub, $item));
                    } else {
                        $q->orWhere(fn ($sub) => $this->buildQuery($sub, $item));
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

    protected function applySort($query, $sort)
    {
        $sorts = is_string($sort) ? json_decode($sort, true) : $sort;
        if (! is_array($sorts)) {
            return;
        }

        foreach ($sorts as $s) {
            $field = $s['selector'] ?? $s['field'] ?? null;
            $direction = ($s['desc'] ?? false) ? 'desc' : 'asc';
            if ($field) {
                $query->orderBy($field, $direction);
            }
        }
    }

    protected function erpResponse($data = null, $message = 'Success', array $tags = [], bool $cache = true)
    {
        if ($data instanceof EloquentBuilder || $data instanceof QueryBuilder) {
            $query = $data;

            $cacheParams = [
                'filter' => Request::input('filter'),
                'sort' => Request::input('sort'),
                'take' => Request::input('take', 10),
                'skip' => Request::input('skip', 0),
                'fields' => Request::input('fields'),
                'expand' => Request::input('expand'),
            ];
            
            $cacheKey = 'erp_cache_' . md5(json_encode($cacheParams));

            $callback = function () use ($query) {
                $this->applyFieldsExpand($query);

                if (Request::has('filter')) {
                    $this->applyFilter($query, Request::input('filter'));
                }

                if (Request::has('sort')) {
                    $this->applySort($query, Request::input('sort'));
                }

                $totalCount = (clone $query)->count();
                $results = $query->takeSkip()->get();

                return [
                    'totalCount' => $totalCount,
                    'data' => $results->toArray(),
                ];
            };

            // Always use tags for cache, add 'all' tag for global cache clearing
            $usedTags = ! empty($tags) ? array_merge($tags, ['all']) : ['all'];
            
            if ($cache) {
                $resultData = CacheService::remember($cacheKey, $usedTags, $callback, 3600);
            } else {
                $resultData = $callback();
            }

            return response()->json($resultData, 200);
        }

        // For non-query responses (messages, etc), clear cache by tags if provided
        if (! empty($tags)) {
            CacheService::clearTags(array_merge($tags, ['all']));
        }

        $response = ['message' => $message];
        if (! is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, 200);
    }
}
