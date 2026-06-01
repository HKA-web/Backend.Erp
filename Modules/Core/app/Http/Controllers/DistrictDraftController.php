<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use App\Services\CacheService;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Requests\DistrictRequest;
use Modules\Core\Models\District;

class DistrictDraftController extends Controller
{
    public function index()
    {
        return $this->erpExecution(function () {
            $query = DB::table('temporary.core_district');

            return $this->erpResponse($query, cache: false);
        });
    }

    public function store(DistrictRequest $request, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request) {
            $staging->executeStaging('core.procedure_upsert_district_draft', $request->validated());

            return $this->erpResponse(
                message: 'Draft District saved successfully.'
            );
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $draft = DB::table('temporary.core_district')
                ->where('temporary_id', $id);

            return $this->erpResponse($draft, cache: false);
        });
    }

    public function update(DistrictRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {
            $payload = array_merge($request->validated(), ['temporary_id' => $id]);

            $staging->executeStaging('core.procedure_upsert_district_draft', $payload);

            return $this->erpResponse(message: 'Draft updated.');
        });
    }

    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {
            DB::table('temporary.core_district')
                ->where('temporary_id', $id)
                ->delete();

            return $this->erpResponse(message: 'Draft discarded.');
        });
    }

    public function commit($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $id) {
            $payload = [
                'temporary_id' => $id,
                'district_id' => request()->input('district_id'),
            ];

            $staging->executeStaging('core.procedure_commit_district', $payload);

            // Clear cache - auto scan relations & tenant aware
            $district = District::find(request()->input('district_id'));
            if ($district) {
                CacheService::clearCache($district);
            }

            return $this->erpResponse(
                message: 'District committed to master successfully.',
                tags: ['district']
            );
        }, 'Failed to commit draft.');
    }
}
