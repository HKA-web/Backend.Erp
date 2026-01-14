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
use App\Traits\HasTransactionResponse;
use App\Traits\Paginatable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use Paginatable, HasTransactionResponse;

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
        return $this->executeTransaction(function () use ($request) {
            $model = new Product();
            $connection = QueryHelper::getConnection($request, $model);

            $data = $request->validated();
            $item = $model;
            if ($connection) $item->setConnection($connection);
            $item->fill($data);
            $item->save();

            return new ProductResource($item);
        }, 'Data created successfully');
    }

    public function update(UpdateProductRequest $request, $id)
    {
        return $this->executeTransaction(function () use ($request, $id) {
            $query = QueryHelper::query(Product::class, $request);
            $item = $query->findOrFail($id);

            $data = $request->validated();
            $item->update($data);

            return new ProductResource($item);
        }, 'Data updated successfully');
    }

    public function destroy(Request $request, $id)
    {
        return $this->executeTransaction(function () use ($request, $id) {
            $query = QueryHelper::query(Product::class, $request);
            $item = $query->findOrFail($id);
            $item->delete();

            return null;
        }, 'Data deleted successfully');
    }
}
