<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Modules\Core\Http\Requests\ProvinceRequest;
use Illuminate\Support\Facades\DB;

class ProvinceDraftController extends Controller
{
    public function index()
    {
        return $this->erpExecution(function () {
            $query = DB::table('temporary.core_province');
            return $this->erpResponse($query);
        });
    }

    public function store(ProvinceRequest $request, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request) {
            $staging->executeStaging('core.procedure_upsert_province_draft', $request->validated());

            return $this->erpResponse(
                message: "Draft Province saved successfully."
            );
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $draft = DB::table('temporary.core_province')
                ->where('temporary_id', $id);

            return $this->erpResponse($draft);
        });
    }

    public function update(ProvinceRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {
            $payload = array_merge($request->validated(), ['temporary_id' => $id]);

            $staging->executeStaging('core.procedure_upsert_province_draft', $payload);

            return $this->erpResponse(message: "Draft updated.");
        });
    }

    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {
            DB::table('temporary.core_province')
                ->where('temporary_id', $id)
                ->delete();

            return $this->erpResponse(message: "Draft discarded.");
        });
    }

    public function commit($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $id) {
            $payload = [
                'temporary_id' => $id,
                'province_id'  => request()->input('province_id')
            ];

            $staging->executeStaging('core.procedure_commit_province', $payload);

            return $this->erpResponse(
                message: "Province committed to master successfully.",
                tags: ['province']
            );
        }, "Failed to commit draft.");
    }
}
