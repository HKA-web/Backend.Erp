<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Modules\Core\Http\Requests\VillageRequest;
use Illuminate\Support\Facades\DB;

class VillageDraftController extends Controller
{
    /**
     * Display all active drafts in the temporary table for current session.
     */
    public function index()
    {
        return $this->erpExecution(function () {
            // Membaca langsung dari tabel temporary
            $query = DB::table('temporary.core_village');
            return $this->erpResponse($query);
        });
    }

    /**
     * Save a new draft.
     */
    public function store(VillageRequest $request, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request) {
            $staging->executeStaging('core.procedure_upsert_village_draft', $request->validated());

            return $this->erpResponse(
                message: "Draft Village saved successfully."
            );
        });
    }

    /**
     * Show draft details from temporary table.
     */
    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $draft = DB::table('temporary.core_village')
                ->where('village_id', $id);

            return $this->erpResponse($draft);
        });
    }

    /**
     * Update existing draft in temporary table.
     */
    public function update(VillageRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {
            $payload = array_merge($request->validated(), ['village_id' => $id]);

            $staging->executeStaging('core.procedure_upsert_village_draft', $payload);

            return $this->erpResponse(message: "Draft updated.");
        });
    }

    /**
     * Discard draft (Delete from temporary).
     */
    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {
            DB::table('temporary.core_village')
                ->where('village_id', $id)
                ->delete();

            return $this->erpResponse(message: "Draft discarded.");
        });
    }

    /**
     * Final Action: Commit Draft to Master.
     * POST /v1/village-drafts/{id}/commit
     */
    public function commit($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $id) {
            $payload = ['village_id' => $id];

            $staging->executeStaging('core.procedure_commit_village', $payload);

            return $this->erpResponse(
                message: "Village committed to master successfully."
            );
        }, "Failed to commit draft.");
    }
}
