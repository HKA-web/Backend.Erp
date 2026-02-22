<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Modules\Core\Http\Requests\DistrictRequest;
use Modules\Core\Models\District;

class DistrictController extends Controller
{
    /**
     * Display a listing of the resource with ERP filtering & sorting.
     */
    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(District::query());
        });
    }

    /**
     * Store a newly created resource in storage (Draft or Posted).
     */
    public function store(DistrictRequest $request, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request) {

            $staging->executeStaging('core.procedure_action_district', $request->validated());

            return $this->erpResponse(
                message: "District {$request->district_name} Successfully Processed."
            );

        }, "Failed to process District.");
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = District::where('district_id', $id);
            return $this->erpResponse($query);
        });
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DistrictRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {

            $validated = $request->validated();

            $payload = array_merge($validated, [
                'district_id' => $id
            ]);

            $staging->executeStaging('core.procedure_action_district', $payload);

            return $this->erpResponse(
                message: "District {$id} ready for editing."
            );

        }, 'Failed to update District.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DistrictRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {

            $validated = $request->validated();

            $payload = array_merge($validated, [
                'district_id' => $id,
                'is_removed' => true
            ]);

            $staging->executeStaging('core.procedure_action_district', $payload);

            return $this->erpResponse(
                message: "District {$id} ready for delete."
            );

        }, 'Failed to delete District.');
    }
}
