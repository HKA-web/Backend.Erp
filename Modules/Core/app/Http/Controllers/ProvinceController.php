<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Modules\Core\Http\Requests\ProvinceRequest;
use Modules\Core\Models\Province;

class ProvinceController extends Controller
{
    /**
     * Display a listing of the resource with ERP filtering & sorting.
     */
    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(Province::query());
        });
    }

    /**
     * Store a newly created resource in storage (Draft or Posted).
     */
    public function store(ProvinceRequest $request, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request) {

            $staging->executeStaging('core.procedure_action_province', $request->validated());

            return $this->erpResponse(
                message: "Province {$request->province_name} Successfully Processed."
            );

        }, "Failed to process Province.");
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = Province::where('province_id', $id);
            return $this->erpResponse($query);
        });
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProvinceRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {

            $validated = $request->validated();

            $payload = array_merge($validated, [
                'province_id' => $id
            ]);

            $staging->executeStaging('core.procedure_action_province', $payload);

            return $this->erpResponse(
                message: "Province {$id} ready for editing."
            );

        }, 'Failed to update Province.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProvinceRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {

            $validated = $request->validated();

            $payload = array_merge($validated, [
                'province_id' => $id,
                'is_removed' => true
            ]);

            $staging->executeStaging('core.procedure_action_province', $payload);

            return $this->erpResponse(
                message: "Province {$id} ready for delete."
            );

        }, 'Failed to delete Province.');
    }
}
