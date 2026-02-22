<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait ViewSets
{
    protected function applyFieldsExpand(Builder $query)
    {
        $fieldsParam = request()->input('fields');
        $expandParam = request()->input('expand');

        if (!$fieldsParam && !$expandParam) return;

        $fields = $fieldsParam ? array_map('trim', explode(',', $fieldsParam)) : [];
        $expands = $expandParam ? array_map('trim', explode(',', $expandParam)) : [];

        // 1. Kelompokkan fields berdasarkan jalurnya (path)
        $fieldTree = [];
        foreach ($fields as $field) {
            $parts = explode('.', $field);
            $column = array_pop($parts);
            $path = implode('.', $parts);
            $fieldTree[$path][] = $column;
        }

        // 2. Terapkan Select untuk Tabel Utama (path kosong "")
        $mainColumns = $fieldTree[""] ?? [];
        if (!empty($mainColumns)) {
            $model = $query->getModel();
            $primaryKey = $model->getKeyName();
            if (!in_array($primaryKey, $mainColumns)) $mainColumns[] = $primaryKey;

            // AMBIL DAFTAR KOLOM ASLI TABEL INI
            $tableName = $model->getTable();
            $realColumns = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);

            // Hanya tambahkan FK jika kolomnya memang eksis di tabel ini (untuk belongsTo)
            foreach ($expands as $rel) {
                $firstPart = explode('.', $rel)[0];
                $fk = Str::snake($firstPart) . '_id';
                if (in_array($fk, $realColumns) && !in_array($fk, $mainColumns)) {
                    $mainColumns[] = $fk;
                }
            }

            // Filter agar tidak ada kolom "gaib" masuk ke SQL
            $mainColumns = array_intersect($mainColumns, $realColumns);
            $query->select($mainColumns);
        }

        // 3. Bangun Nested Eager Loading
        $withRelations = [];
        foreach ($expands as $relPath) {
            $withRelations[$relPath] = function ($q) use ($relPath, $fieldTree) {
                $model = $q->getModel();
                $tableName = $model->getTable();
                $realColumns = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);

                if (isset($fieldTree[$relPath])) {
                    $cols = $fieldTree[$relPath];

                    // Selalu sertakan PK relasi
                    $pk = $model->getKeyName();
                    if (!in_array($pk, $cols)) $cols[] = $pk;

                    // Cek Level Dibawahnya (untuk nested)
                    foreach ($fieldTree as $path => $columns) {
                        if (str_starts_with($path, $relPath . '.')) {
                            $subRel = str_replace($relPath . '.', '', $path);
                            $fk = Str::snake($subRel) . '_id';

                            // HANYA tambah kolom jika dia ada di tabel (belongsTo scenario)
                            if (in_array($fk, $realColumns) && !in_array($fk, $cols)) {
                                $cols[] = $fk;
                            }
                        }
                    }

                    // JIKA ini adalah hasMany, kita butuh FK dari Parent agar Eloquent bisa mapping
                    // Contoh: Di tabel City, kita butuh 'province_id' agar bisa nyambung ke Province
                    $parentPath = str_contains($relPath, '.') ? Str::beforeLast($relPath, '.') : null;
                    // Sederhananya, pastikan semua kolom yang berakhiran _id ikut jika ada di tabel
                    foreach ($realColumns as $col) {
                        if (str_ends_with($col, '_id') && !in_array($col, $cols)) {
                            // Cek apakah kolom ini mungkin penghubung ke parent/child
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

    public function applyFilter(Builder $query, $filter)
    {
        if (empty($filter)) return $query;

        if (is_string($filter)) {
            $filter = json_decode($filter, true);
        }

        return $this->buildQuery($query, $filter);
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

    protected function applySort(Builder $query, $sort)
    {
        $sorts = is_string($sort) ? json_decode($sort, true) : $sort;
        if (!is_array($sorts)) return;

        foreach ($sorts as $s) {
            $field = $s['selector'] ?? $s['field'] ?? null;
            $direction = ($s['desc'] ?? false) ? 'desc' : 'asc';
            if ($field) $query->orderBy($field, $direction);
        }
    }

    protected function erpResponse(Builder $query)
    {
        $this->applyFieldsExpand($query);

        if (request()->has('filter')) {
            $this->applyFilter($query, request()->input('filter'));
        }

        if (request()->has('sort')) {
            $this->applySort($query, request()->input('sort'));
        }

        $totalCount = (clone $query)->count();

        $data = $query->takeSkip()->get();

        return response()->json([
            'totalCount' => $totalCount,
            'data'       => $data
        ], 200);
    }
}
