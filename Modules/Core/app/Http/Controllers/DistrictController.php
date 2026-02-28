<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Modules\Core\Models\District;
use Illuminate\Http\Request;

class DistrictController extends Controller
{
    /**
     * Display a listing of final posted resources.
     */
    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(District::query());
        });
    }

    /**
     * Show the specified final resource.
     */
    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = District::where('district_id', $id);
            return $this->erpResponse($query);
        });
    }

    /**
     * Action to pull Master data into Draft for revision.
     * POST /v1/{{model_plural_lower}}/{id}/revise
     */
    public function revise($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $id) {

            $staging->executeStaging('core.procedure_revise_district', [
                'district_id' => $id
            ]);

            return $this->erpResponse(
                message: "District {$id} has been moved to drafts for revision."
            );
        }, "Failed to initiate revision for District.");
    }

    /**
     * Remove the specified resource (Encapsulated logic in BaseStaging).
     * DELETE /v1/{{model_plural_lower}}/{id}
     */
    public function destroy($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($id, $staging) {

            // Logika pengecekan trait & eksekusi SP ada di dalam sini
            $staging->requestDelete(District::class, $id, 'core.procedure_upsert_district_draft');

            return $this->erpResponse(
                message: "Delete request for District {$id} processed according to model policy."
            );

        }, "Failed to process delete request for District.");
    }
}
