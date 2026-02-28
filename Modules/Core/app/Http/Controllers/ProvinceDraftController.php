<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Modules\Core\Http\Requests\ProvinceRequest;
use Illuminate\Support\Facades\DB;

class ProvinceDraftController extends Controller
{
    /**
     * Display all active drafts in the temporary table for current session.
     */
    public function index()
    {
        return $this->erpExecution(function () {
            // Membaca langsung dari tabel temporary
            $query = DB::table('temporary.core_province');
            return $this->erpResponse($query);
        });
    }

    /**
     * Save a new draft.
     */
    public function store(ProvinceRequest $request, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request) {
            $staging->executeStaging('core.procedure_upsert_province_draft', $request->validated());

            return $this->erpResponse(
                message: "Draft Province saved successfully."
            );
        });
    }

    /**
     * Show draft details from temporary table.
     */
    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $draft = DB::table('temporary.core_province')
                ->where('province_id', $id);

            return $this->erpResponse($draft);
        });
    }

    /**
     * Update existing draft in temporary table.
     */
    public function update(ProvinceRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {
            $payload = array_merge($request->validated(), ['province_id' => $id]);

            $staging->executeStaging('core.procedure_upsert_province_draft', $payload);

            return $this->erpResponse(message: "Draft updated.");
        });
    }

    /**
     * Discard draft (Delete from temporary).
     */
    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {
            DB::table('temporary.core_province')
                ->where('province_id', $id)
                ->delete();

            return $this->erpResponse(message: "Draft discarded.");
        });
    }

    /**
     * Final Action: Commit Draft to Master.
     * POST /v1/province-drafts/{id}/commit
     */
    public function commit($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $id) {
            $payload = ['province_id' => $id];

            $staging->executeStaging('core.procedure_commit_province', $payload);

            return $this->erpResponse(
                message: "Province committed to master successfully."
            );
        }, "Failed to commit draft.");
    }
}
