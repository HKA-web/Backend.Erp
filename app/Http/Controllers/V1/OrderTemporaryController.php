<?php

namespace App\Http\Controllers\V1;

use App\Helpers\DebugHelper;
use App\Helpers\ExpandHelper;
use App\Helpers\QueryHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderTemporary\StoreOrderTemporaryRequest;
use App\Http\Requests\OrderTemporary\UpdateOrderTemporaryRequest;
use App\Http\Resources\OrderTemporary\OrderTemporaryResource;
use App\Models\OrderTemporary;
use App\Models\Order;
use App\Traits\HasTransactionResponse;
use App\Traits\Paginatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderTemporaryController extends Controller
{
    use Paginatable, HasTransactionResponse;

    public function index(Request $request)
    {
        try {
            $model = new OrderTemporary();
            $connection = QueryHelper::getConnection($request, $model);
            $expandTree = ExpandHelper::parse($request->query('expand'));
            $with = ExpandHelper::toWith($expandTree);
            $sessionId = $request->query('session_id');

            $filterExpr = $this->getFilterExpression($request);

            $baseQuery = QueryHelper::newQuery($model, $connection);
            $baseQuery = $this->applyExpressionFilter($baseQuery, $filterExpr);

            $query = clone $baseQuery;
            $query = $query->with($with);

            if ($sessionId) {
                $query->where('session_id', $sessionId);
            }

            $pagination = $this->paginateQuery($query, $request);

            return response()->json(array_merge([
                'connection' => Str::studly($connection),
                'status'     => 'success',
                'message'    => 'List data retrieved successfully',
                'expand'     => $expandTree,
            ], $pagination, [
                'data' => OrderTemporaryResource::collection($pagination['data']),
            ]));
        } catch (\Throwable $e) {
            return response()->json([
                'connection' => QueryHelper::getConnection($request, new OrderTemporary()),
                'status'     => 'error',
                'message'    => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving data.',
                'trace'      => config('app.debug') ? DebugHelper::formatTrace($e) : null,
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $model = new OrderTemporary();
            $connection = QueryHelper::getConnection($request, $model);

            $expandTree = ExpandHelper::parse($request->query('expand'));
            $with = ExpandHelper::toWith($expandTree);

            $filterExpr = $this->getFilterExpression($request);

            $baseQuery = QueryHelper::newQuery($model, $connection)->with($with);
            $baseQuery = $this->applyExpressionFilter($baseQuery, $filterExpr);

            $query = clone $baseQuery;
            $query->where('temporary_id', $id);

            $data = $query->firstOrFail();

            return response()->json(array_merge([
                'connection' => Str::studly($connection),
                'status'     => 'success',
                'message'    => 'List data retrieved successfully',
                'expand'     => $expandTree,
                'data' => new OrderTemporaryResource($data),
            ]));
        } catch (\Throwable $e) {
            return response()->json([
                'connection' => QueryHelper::getConnection($request, new OrderTemporary()),
                'status'     => 'error',
                'message'    => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving data.',
                'trace'      => config('app.debug') ? DebugHelper::formatTrace($e) : null,
            ], 500);
        }
    }

    public function store(StoreOrderTemporaryRequest $request)
    {
        return $this->executeTransaction(function () use ($request) {
            $model = new OrderTemporary();
            $connection = QueryHelper::getConnection($request, $model);

            if ($connection) $model->setConnection($connection);

            $model->fill($request->validated());
            $model->save();

            return new OrderTemporaryResource($model);
        }, 'Data created successfully');
    }

    public function update(UpdateOrderTemporaryRequest $request, $id)
    {
        return $this->executeTransaction(function () use ($request, $id) {
            $query = QueryHelper::query(OrderTemporary::class, $request);
            $item  = $query->findOrFail($id);

            $item->update($request->validated());

            return new OrderTemporaryResource($item);
        }, 'Data updated successfully');
    }

    public function destroy(Request $request, $id)
    {
        return $this->executeTransaction(function () use ($request, $id) {
            $query = QueryHelper::query(OrderTemporary::class, $request);
            $item  = $query->findOrFail($id);
            $item->delete();

            return null;
        }, 'Data deleted successfully');
    }

    public function commit(Request $request)
    {
        return $this->executeTransaction(function () use ($request) {

            $sessionId = $request->input('session_id');

            $rows = OrderTemporary::where('session_id', $sessionId)->get();

            foreach ($rows as $row) {
                $data = $row->toArray();

                unset(
                    $data['temporary_id'],
                    $data['session_id'],
                    $data['created_at'],
                    $data['updated_at']
                );

                Order::updateOrCreate(
                    ['order_id' => $data['order_id']],
                    $data
                );
            }

            OrderTemporary::where('session_id', $sessionId)->delete();

        }, 'Transaction committed successfully');
    }
}