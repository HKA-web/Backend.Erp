<?php

namespace App\Http\Controllers\V1;

use App\Helpers\DebugHelper;
use App\Helpers\ExpandHelper;
use App\Helpers\QueryHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Province\StoreProvinceRequest;
use App\Http\Requests\Province\UpdateProvinceRequest;
use App\Http\Resources\Province\ProvinceResource;
use App\Models\Province;
use App\Traits\Paginatable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProvinceController extends Controller
{
    use Paginatable;

    public function index(Request $request)
    {
        try {
            $model = new Province();
            $connection = QueryHelper::getConnection($request, $model);

            $expandTree = ExpandHelper::parse($request->query('expand'));
            $with = ExpandHelper::toWith($expandTree);

            $filterExpr = $this->getFilterExpression($request);

            $baseQuery = QueryHelper::newQuery($model, $connection);
            $baseQuery = $this->applyExpressionFilter($baseQuery, $filterExpr);

            $query = clone $baseQuery;
            $query = $query->with($with);

            $pagination = $this->paginateQuery($query, $request);

            return response()->json(array_merge([
                'connection' => Str::studly($connection),
                'status'     => Str::studly('success'),
                'message'    => 'List data retrieved successfully',
                'expand'     => $expandTree,
            ], $pagination, [
                'data' => ProvinceResource::collection($pagination['data']),
            ]));
        } catch (\Throwable $e) {
            return response()->json([
                'connection' => QueryHelper::getConnection($request, new Province()),
                'status'     => Str::studly('error'),
                'message'    => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving data.',
                'trace'      => config('app.debug') ? DebugHelper::formatTrace($e) : null,
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $model = new Province();
            $connection = QueryHelper::getConnection($request, $model);

            $expandTree = ExpandHelper::parse($request->query('expand'));
            $with = ExpandHelper::toWith($expandTree);

            $filterExpr = $this->getFilterExpression($request);

            $query = QueryHelper::newQuery($model, $connection)->with($with);
            $query = $this->applyExpressionFilter($query, $filterExpr);
            $query->where('province_id', $id);

            $data = $query->firstOrFail();

            return response()->json([
                'connection' => Str::studly($connection),
                'status'     => Str::studly('success'),
                'message'    => 'Data retrieved successfully',
                'expand'     => $expandTree,
                'data'       => new ProvinceResource($data),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'connection' => QueryHelper::getConnection($request, new Province()),
                'status'     => Str::studly('error'),
                'message'    => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving data.',
                'trace'      => config('app.debug') ? DebugHelper::formatTrace($e) : null,
            ], 500);
        }
    }

    public function store(StoreProvinceRequest $request)
    {
        try {
            $model = new Province();
            $connection = QueryHelper::getConnection($request, $model);

            $data = $request->validated();
            $province = $model;
            if ($connection) $province->setConnection($connection);
            $province->fill($data);
            $province->save();

            return response()->json([
                'connection' => Str::studly($connection),
                'status'     => Str::studly('success'),
                'message'    => 'Data created successfully',
                'data'       => new ProvinceResource($province),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'connection' => QueryHelper::getConnection($request, new Province()),
                'status'     => Str::studly('error'),
                'message'    => config('app.debug') ? $e->getMessage() : 'An error occurred while creating data.',
                'trace'      => config('app.debug') ? DebugHelper::formatTrace($e) : null,
            ], 500);
        }
    }

    public function update(UpdateProvinceRequest $request, $id)
    {
        try {
            $query = QueryHelper::query(Province::class, $request);
            $province = $query->findOrFail($id);

            $data = $request->validated();
            $province->update($data);

            return response()->json([
                'connection' => QueryHelper::getConnection($request, new Province()),
                'status'     => Str::studly('success'),
                'message'    => 'Data updated successfully',
                'data'       => new ProvinceResource($province),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'connection' => QueryHelper::getConnection($request, new Province()),
                'status'     => Str::studly('error'),
                'message'    => config('app.debug') ? $e->getMessage() : 'An error occurred while updating data.',
                'trace'      => config('app.debug') ? DebugHelper::formatTrace($e) : null,
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $query = QueryHelper::query(Province::class, $request);
            $province = $query->findOrFail($id);
            $province->delete();

            return response()->json([
                'connection' => QueryHelper::getConnection($request, new Province()),
                'status'     => Str::studly('success'),
                'message'    => 'Data deleted successfully',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'connection' => QueryHelper::getConnection($request, new Province()),
                'status'     => Str::studly('error'),
                'message'    => config('app.debug') ? $e->getMessage() : 'An error occurred while deleting data.',
                'trace'      => config('app.debug') ? DebugHelper::formatTrace($e) : null,
            ], 500);
        }
    }
}
