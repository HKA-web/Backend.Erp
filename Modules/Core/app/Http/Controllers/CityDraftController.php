<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Requests\CityRequest;

class CityDraftController extends Controller
{
    public function index()
    {
        return $this->erpExecution(function () {
            $query = DB::table('temporary.core_city');

            return $this->erpResponse($query);
        });
    }

    public function store(CityRequest $request, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request) {
            $staging->executeStaging('core.procedure_upsert_city_draft', $request->validated());

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

            return $this->erpResponse($draft);
        });
    }

    public function update(CityRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {
            $payload = array_merge($request->validated(), ['temporary_id' => $id]);

            $staging->executeStaging('core.procedure_upsert_city_draft', $payload);

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

    public function commit($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $id) {
            $payload = [
                'temporary_id' => $id,
                'city_id' => request()->input('city_id'),
            ];

            $staging->executeStaging('core.procedure_commit_city', $payload);

            return $this->erpResponse(
                message: 'City committed to master successfully.',
                tags: ['city']
            );
        }, 'Failed to commit draft.');
    }
}
