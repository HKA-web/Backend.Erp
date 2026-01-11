<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected array $allowedFilters = [];

    protected function getFilterExpression(\Illuminate\Http\Request $request): ?array
    {
        $filters = $request->input('filters');

        if (is_string($filters)) {
            $decoded = json_decode($filters, true);
            return is_array($decoded) ? $decoded : null;
        }

        return is_array($filters) ? $filters : null;
    }

    protected function applyExpressionFilter($query, ?array $expression)
    {
        if (!$expression || count($expression) === 0) {
            return $query;
        }

        // jika 1D array 3 elemen → bungkus jadi 2D
        if (count($expression) === 3 && is_string($expression[0]) && is_string($expression[1])) {
            $expression = [$expression]; // ubah jadi 2D
        }

        $driver = $query->getConnection()->getDriverName();
        $likeOp = in_array($driver, ['pgsql']) ? 'ILIKE' : 'LIKE';

        $currentLogic = 'and';

        foreach ($expression as $part) {

            // LOGIC
            if (is_string($part)) {
                $logic = strtolower($part);
                if (in_array($logic, ['and', 'or'])) {
                    $currentLogic = $logic;
                }
                continue;
            }

            // CONDITION
            if (!is_array($part) || count($part) < 3) {
                continue;
            }

            [$field, $op, $value] = $part;

            // whitelist
            if (!empty($this->allowedFilters) && !in_array($field, $this->allowedFilters)) {
                continue;
            }

            $op = strtolower($op);
            $method = $currentLogic === 'or' ? 'orWhere' : 'where';

            switch ($op) {
                case '=':
                case 'eq':
                    $query->{$method}($field, '=', $value);
                    break;

                case '>':
                case 'gt':
                    $query->{$method}($field, '>', $value);
                    break;

                case '<':
                case 'lt':
                    $query->{$method}($field, '<', $value);
                    break;

                case 'like':
                case 'contains':
                    $query->{$method}($field, $likeOp, "%{$value}%");
                    break;

                case 'ilike':
                case 'icontains':
                    $query->{$method}($field, $likeOp, "%{$value}%");
                    break;

                default:
                    $query->{$method}($field, '=', $value);
            }
        }

        return $query;
    }

}
