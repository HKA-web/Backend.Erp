<?php

namespace App\Http\Controllers\V1;

use App\Helpers\DebugHelper;
use App\Helpers\ExpandHelper;
use App\Helpers\QueryHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product;
use App\Traits\Paginatable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use Paginatable;

    public function index(Request $request)
    {
        try {
            $model = new Product();
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
                'data' => ProductResource::collection($pagination['data']),
            ]));
        } catch (\Throwable $e) {
            return response()->json([
                'connection' => QueryHelper::getConnection($request, new Product()),
                'status'     => Str::studly('error'),
                'message'    => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving data.',
                'trace'      => config('app.debug') ? DebugHelper::formatTrace($e) : null,
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $model = new Product();
            $connection = QueryHelper::getConnection($request, $model);

            $expandTree = ExpandHelper::parse($request->query('expand'));
            $with = ExpandHelper::toWith($expandTree);

            $filterExpr = $this->getFilterExpression($request);

            $query = QueryHelper::newQuery($model, $connection)->with($with);
            $query = $this->applyExpressionFilter($query, $filterExpr);
            $query->where('product_id', $id);

            $data = $query->firstOrFail();

            return response()->json([
                'connection' => Str::studly($connection),
                'status'     => Str::studly('success'),
                'message'    => 'Data retrieved successfully',
                'expand'     => $expandTree,
                'data'       => new ProductResource($data),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'connection' => QueryHelper::getConnection($request, new Product()),
                'status'     => Str::studly('error'),
                'message'    => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving data.',
                'trace'      => config('app.debug') ? DebugHelper::formatTrace($e) : null,
            ], 500);
        }
    }

    public function store(StoreProductRequest $request)
    {
        try {
            $model = new Product();
            $connection = QueryHelper::getConnection($request, $model);

            $data = $request->validated();
            $item = $model;
            if ($connection) $item->setConnection($connection);
            $item->fill($data);
            $item->save();

            return response()->json([
                'connection' => Str::studly($connection),
                'status'     => Str::studly('success'),
                'message'    => 'Data created successfully',
                'data'       => new ProductResource($item),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'connection' => QueryHelper::getConnection($request, new Product()),
                'status'     => Str::studly('error'),
                'message'    => config('app.debug') ? $e->getMessage() : 'An error occurred while creating data.',
                'trace'      => config('app.debug') ? DebugHelper::formatTrace($e) : null,
            ], 500);
        }
    }

    public function update(UpdateProductRequest $request, $id)
    {
        try {
            $query = QueryHelper::query(Product::class, $request);
            $item = $query->findOrFail($id);

            $data = $request->validated();
            $item->update($data);

            return response()->json([
                'connection' => QueryHelper::getConnection($request, new Product()),
                'status'     => Str::studly('success'),
                'message'    => 'Data updated successfully',
                'data'       => new ProductResource($item),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'connection' => QueryHelper::getConnection($request, new Product()),
                'status'     => Str::studly('error'),
                'message'    => config('app.debug') ? $e->getMessage() : 'An error occurred while updating data.',
                'trace'      => config('app.debug') ? DebugHelper::formatTrace($e) : null,
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $query = QueryHelper::query(Product::class, $request);
            $item = $query->findOrFail($id);
            $item->delete();

            return response()->json([
                'connection' => QueryHelper::getConnection($request, new Product()),
                'status'     => Str::studly('success'),
                'message'    => 'Data deleted successfully',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'connection' => QueryHelper::getConnection($request, new Product()),
                'status'     => Str::studly('error'),
                'message'    => config('app.debug') ? $e->getMessage() : 'An error occurred while deleting data.',
                'trace'      => config('app.debug') ? DebugHelper::formatTrace($e) : null,
            ], 500);
        }
    }
}