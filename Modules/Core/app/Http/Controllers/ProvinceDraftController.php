<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Requests\ProvinceRequest;
use Modules\Core\Models\Province;

class ProvinceDraftController extends Controller
{
    public function __construct(protected readonly BaseService $baseService) {}

    public function index()
    {
        return $this->erpExecution(function () {
            $query = DB::table('temporary.core_province');

            return $this->erpResponse($query, cache: false);
        });
    }

    public function store(ProvinceRequest $request)
    {
        return $this->erpExecution(function () use ($request) {
            $this->baseService->executeProcedure('core.procedure_upsert_province_draft', $request->validated());

            return $this->erpResponse(
                message: 'Draft Province saved successfully.'
            );
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $draft = DB::table('temporary.core_province')
                ->where('temporary_id', $id);

            return $this->erpResponse($draft, cache: false);
        });
    }

    public function update(ProvinceRequest $request, $id)
    {
        return $this->erpExecution(function () use ($request, $id) {
            $payload = array_merge($request->validated(), ['temporary_id' => $id]);

            $this->baseService->executeProcedure('core.procedure_upsert_province_draft', $payload);

            return $this->erpResponse(message: 'Draft updated.');
        });
    }

    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {
            DB::table('temporary.core_province')
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
                'province_id' => request()->input('province_id'),
            ];

            $this->baseService->executeProcedure('core.procedure_commit_province', $payload, Province::class);

                        

            return $this->erpResponse(
                message: 'Province committed to master successfully.',
                tags: ['province']
            );
        }, 'Failed to commit draft.');
    }
}
