<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Requests\CityRequest;
use Modules\Core\Models\City;

class CityDraftController extends Controller
{
    public function __construct(protected readonly BaseService $baseService) {}

    public function index()
    {
        return $this->erpExecution(function () {
            $query = DB::table('temporary.core_city');

            return $this->erpResponse($query, cache: false);
        });
    }

    public function store(CityRequest $request)
    {
        return $this->erpExecution(function () use ($request) {
            $this->baseService->executeProcedure('core.procedure_upsert_city_draft', $request->validated());

            return $this->erpResponse(
                message: 'Draft City saved successfully.'
            );
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $draft = DB::table('temporary.core_city')
                ->where('temporary_id', $id);

            return $this->erpResponse($draft, cache: false);
        });
    }

    public function update(CityRequest $request, $id)
    {
        return $this->erpExecution(function () use ($request, $id) {
            $payload = array_merge($request->validated(), ['temporary_id' => $id]);

            $this->baseService->executeProcedure('core.procedure_upsert_city_draft', $payload);

            return $this->erpResponse(message: 'Draft updated.');
        });
    }

    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {
            DB::table('temporary.core_city')
                ->where('temporary_id', $id)
                ->delete();

            return $this->erpResponse(message: 'Draft discarded.');
        });
    }

    public function commit($id)
    {
        return $this->erpExecution(function () use ($id) {
            $payload = [
                'temporary_id' => $id,
                'city_id' => request()->input('city_id'),
            ];

            $this->baseService->executeProcedure('core.procedure_commit_city', $payload, City::class);

                        

            return $this->erpResponse(
                message: 'City committed to master successfully.',
                tags: ['city']
            );
        }, 'Failed to commit draft.');
    }
}
