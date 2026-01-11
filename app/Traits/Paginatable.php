<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait Paginatable
{
    public function paginateQuery($query, Request $request, int $maxTake = 100)
    {
        $skip = $request->query('skip');
        $take = $request->query('take');

        $total = $query->count();

        if ($skip !== null) {
            $skip = (int) $skip;
            if ($skip > 0) $query = $query->skip($skip);
        } else {
            $skip = 0;
        }

        if ($take !== null) {
            $take = min((int) $take, $maxTake);
            if ($take > 0) $query = $query->take($take);
        } else {
            $take = $total;
        }

        $data = $query->get();

        return [
            'take'        => $take,
            'skip'        => $skip,
            'totalCount'  => $data->count(),
            'total'       => $total,
            'data'        => $data,
        ];
    }
}
