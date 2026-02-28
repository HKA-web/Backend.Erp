<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Modules\Core\Http\Requests\DistrictRequest;
use Illuminate\Support\Facades\DB;

class DistrictDraftController extends Controller
{
    /**
     * Display all active drafts in the temporary table for current session.
     */
    public function index()
    {
        return $this->erpExecution(function () {
            // Membaca langsung dari tabel temporary
            $query = DB::table('temporary.core_district');
            return $this->erpResponse($query);
        });
    }

    /**
     * Save a new draft.
     */
    public function store(DistrictRequest $request, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request) {
            $staging->executeStaging('core.procedure_upsert_district_draft', $request->validated());

            return $this->erpResponse(
                message: "Draft District saved successfully."
            );
        });
    }

    /**
     * Show draft details from temporary table.
     */
    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $draft = DB::table('temporary.core_district')
                ->where('district_id', $id);

            return $this->erpResponse($draft);
        });
    }

    /**
     * Update existing draft in temporary table.
     */
    public function update(DistrictRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {
            $payload = array_merge($request->validated(), ['district_id' => $id]);

            $staging->executeStaging('core.procedure_upsert_district_draft', $payload);

            return $this->erpResponse(message: "Draft updated.");
        });
    }

    /**
     * Discard draft (Delete from temporary).
     */
    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {
            DB::table('temporary.core_district')
                ->where('district_id', $id)
                ->delete();

            return $this->erpResponse(message: "Draft discarded.");
        });
    }

    /**
     * Final Action: Commit Draft to Master.
     * POST /v1/district-drafts/{id}/commit
     */
    public function commit($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $id) {
            $payload = ['district_id' => $id];

            $staging->executeStaging('core.procedure_commit_district', $payload);

            return $this->erpResponse(
                message: "District committed to master successfully."
            );
        }, "Failed to commit draft.");
    }
}
