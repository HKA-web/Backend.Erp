<?php

namespace App\Http\Controllers\V1;

use App\Helpers\DebugHelper;
use App\Helpers\ExpandHelper;
use App\Helpers\QueryHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreCompanyRequest;
use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Http\Resources\Company\CompanyResource;
use App\Models\Company;
use App\Traits\Paginatable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    use Paginatable;

    public function index(Request $request)
    {
        try {
            $model = new Company();
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
                'data' => CompanyResource::collection($pagination['data']),
            ]));
        } catch (\Throwable $e) {
            return response()->json([
                'connection' => QueryHelper::getConnection($request, new Company()),
                'status'     => Str::studly('error'),
                'message'    => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving data.',
                'trace'      => config('app.debug') ? DebugHelper::formatTrace($e) : null,
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $model = new Company();
            $connection = QueryHelper::getConnection($request, $model);

            $expandTree = ExpandHelper::parse($request->query('expand'));
            $with = ExpandHelper::toWith($expandTree);

            $filterExpr = $this->getFilterExpression($request);

            $query = QueryHelper::newQuery($model, $connection)->with($with);
            $query = $this->applyExpressionFilter($query, $filterExpr);
            $query->where('company_id', $id);

            $data = $query->firstOrFail();

            return response()->json([
                'connection' => Str::studly($connection),
                'status'     => Str::studly('success'),
                'message'    => 'Data retrieved successfully',
                'expand'     => $expandTree,
                'data'       => new CompanyResource($data),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'connection' => QueryHelper::getConnection($request, new Company()),
                'status'     => Str::studly('error'),
                'message'    => config('app.debug') ? $e->getMessage() : 'An error occurred while retrieving data.',
                'trace'      => config('app.debug') ? DebugHelper::formatTrace($e) : null,
            ], 500);
        }
    }

    public function store(StoreCompanyRequest $request)
    {
        try {
            $model = new Company();
            $connection = QueryHelper::getConnection($request, $model);

            $data = $request->validated();
            $company = $model;
            if ($connection) $company->setConnection($connection);
            $company->fill($data);
            $company->save();

            return response()->json([
                'connection' => Str::studly($connection),
                'status'     => Str::studly('success'),
                'message'    => 'Data created successfully',
                'data'       => new CompanyResource($company),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'connection' => QueryHelper::getConnection($request, new Company()),
                'status'     => Str::studly('error'),
                'message'    => config('app.debug') ? $e->getMessage() : 'An error occurred while creating data.',
                'trace'      => config('app.debug') ? DebugHelper::formatTrace($e) : null,
            ], 500);
        }
    }

    public function update(UpdateCompanyRequest $request, $id)
    {
        try {
            $query = QueryHelper::query(Company::class, $request);
            $company = $query->findOrFail($id);

            $data = $request->validated();
            $company->update($data);

            return response()->json([
                'connection' => QueryHelper::getConnection($request, new Company()),
                'status'     => Str::studly('success'),
                'message'    => 'Data updated successfully',
                'data'       => new CompanyResource($company),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'connection' => QueryHelper::getConnection($request, new Company()),
                'status'     => Str::studly('error'),
                'message'    => config('app.debug') ? $e->getMessage() : 'An error occurred while updating data.',
                'trace'      => config('app.debug') ? DebugHelper::formatTrace($e) : null,
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $query = QueryHelper::query(Company::class, $request);
            $company = $query->findOrFail($id);
            $company->delete();

            return response()->json([
                'connection' => QueryHelper::getConnection($request, new Company()),
                'status'     => Str::studly('success'),
                'message'    => 'Data deleted successfully',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'connection' => QueryHelper::getConnection($request, new Company()),
                'status'     => Str::studly('error'),
                'message'    => config('app.debug') ? $e->getMessage() : 'An error occurred while deleting data.',
                'trace'      => config('app.debug') ? DebugHelper::formatTrace($e) : null,
            ], 500);
        }
    }
}
