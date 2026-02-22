<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Modules\Core\Http\Requests\CityRequest;
use Modules\Core\Models\City;

class CityController extends Controller
{
    /**
     * Display a listing of the resource with ERP filtering & sorting.
     */
    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(City::query());
        });
    }

    /**
     * Store a newly created resource in storage (Draft or Posted).
     */
    public function store(CityRequest $request, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request) {

            $staging->executeStaging('core.procedure_action_city', $request->validated());

            return $this->erpResponse(
                message: "City {$request->city_name} Successfully Processed."
            );

        }, "Failed to process City.");
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = City::where('city_id', $id);
            return $this->erpResponse($query);
        });
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CityRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {

            $validated = $request->validated();

            $payload = array_merge($validated, [
                'city_id' => $id
            ]);

            $staging->executeStaging('core.procedure_action_city', $payload);

            return $this->erpResponse(
                message: "City {$id} ready for editing."
            );

        }, 'Failed to update City.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CityRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {

            $validated = $request->validated();

            $payload = array_merge($validated, [
                'city_id' => $id,
                'is_removed' => true
            ]);

            $staging->executeStaging('core.procedure_action_city', $payload);

            return $this->erpResponse(
                message: "City {$id} ready for delete."
            );

        }, 'Failed to delete City.');
    }
}
