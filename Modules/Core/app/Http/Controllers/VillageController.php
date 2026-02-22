<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Modules\Core\Http\Requests\VillageRequest;
use Modules\Core\Models\Village;

class VillageController extends Controller
{
    /**
     * Display a listing of the resource with ERP filtering & sorting.
     */
    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(Village::query());
        });
    }

    /**
     * Store a newly created resource in storage (Draft or Posted).
     */
    public function store(VillageRequest $request, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request) {

            $staging->executeStaging('core.procedure_action_village', $request->validated());

            return $this->erpResponse(
                message: "Village {$request->village_name} Successfully Processed."
            );

        }, "Failed to process Village.");
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = Village::where('village_id', $id);
            return $this->erpResponse($query);
        });
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(VillageRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {

            $validated = $request->validated();

            $payload = array_merge($validated, [
                'village_id' => $id
            ]);

            $staging->executeStaging('core.procedure_action_village', $payload);

            return $this->erpResponse(
                message: "Village {$id} ready for editing."
            );

        }, 'Failed to update Village.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VillageRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {

            $validated = $request->validated();

            $payload = array_merge($validated, [
                'village_id' => $id,
                'is_removed' => true
            ]);

            $staging->executeStaging('core.procedure_action_village', $payload);

            return $this->erpResponse(
                message: "Village {$id} ready for delete."
            );

        }, 'Failed to delete Village.');
    }
}
