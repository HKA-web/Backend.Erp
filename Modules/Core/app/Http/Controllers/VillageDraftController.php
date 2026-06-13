<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Requests\VillageRequest;
use Modules\Core\Models\Village;

class VillageDraftController extends Controller
{
    public function __construct(protected readonly BaseService $baseService) {}

    public function index()
    {
        return $this->erpExecution(function () {
            // Membaca langsung dari tabel temporary
            $query = DB::table('temporary.core_village');

            return $this->erpResponse($query, cache: false);
        });
    }

    public function store(VillageRequest $request)
    {
        return $this->erpExecution(function () use ($request) {
            $this->baseService->executeProcedure('core.procedure_upsert_village_draft', $request->validated());

            return $this->erpResponse(
                message: 'Draft Village saved successfully.'
            );
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $draft = DB::table('temporary.core_village')
                ->where('temporary_id', $id);

            return $this->erpResponse($draft, cache: false);
        });
    }

    public function update(VillageRequest $request, $id)
    {
        return $this->erpExecution(function () use ($request, $id) {
            $payload = array_merge($request->validated(), ['temporary_id' => $id]);

            $this->baseService->executeProcedure('core.procedure_upsert_village_draft', $payload);

            return $this->erpResponse(message: 'Draft updated.');
        });
    }

    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {
            DB::table('temporary.core_village')
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
                'village_id' => request()->input('village_id'),
            ];

            $this->baseService->executeProcedure('core.procedure_commit_village', $payload, Village::class);

                        

            return $this->erpResponse(
                message: 'Village committed to master successfully.',
                tags: ['village']
            );
        }, 'Failed to commit draft.');
    }
}
