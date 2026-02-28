<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Modules\Core\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    /**
     * Display a listing of final posted resources.
     */
    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(City::query());
        });
    }

    /**
     * Show the specified final resource.
     */
    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = City::where('city_id', $id);
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

            $staging->executeStaging('core.procedure_revise_city', [
                'city_id' => $id
            ]);

            return $this->erpResponse(
                message: "City {$id} has been moved to drafts for revision."
            );
        }, "Failed to initiate revision for City.");
    }

    /**
     * Remove the specified resource (Encapsulated logic in BaseStaging).
     * DELETE /v1/{{model_plural_lower}}/{id}
     */
    public function destroy($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($id, $staging) {

            // Logika pengecekan trait & eksekusi SP ada di dalam sini
            $staging->requestDelete(City::class, $id, 'core.procedure_upsert_city_draft');

            return $this->erpResponse(
                message: "Delete request for City {$id} processed according to model policy."
            );

        }, "Failed to process delete request for City.");
    }
}
