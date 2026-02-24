<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Modules\Core\Http\Requests\DictionaryRequest;
use Modules\Core\Models\Dictionary;

class DictionaryController extends Controller
{
    /**
     * Display a listing of the resource with ERP filtering & sorting.
     */
    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(Dictionary::query());
        });
    }

    /**
     * Store a newly created resource in storage (Draft or Posted).
     */
    public function store(DictionaryRequest $request, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request) {

            $staging->executeStaging('core.procedure_action_dictionary', $request->validated());

            return $this->erpResponse(
                message: "Dictionary {$request->dictionary_name} Successfully Processed."
            );

        }, "Failed to process Dictionary.");
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = Dictionary::where('dictionary_id', $id);
            return $this->erpResponse($query);
        });
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DictionaryRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {

            $validated = $request->validated();

            $payload = array_merge($validated, [
                'dictionary_id' => $id
            ]);

            $staging->executeStaging('core.procedure_action_dictionary', $payload);

            return $this->erpResponse(
                message: "Dictionary {$id} ready for editing."
            );

        }, 'Failed to update Dictionary.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DictionaryRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {

            $validated = $request->validated();

            $payload = array_merge($validated, [
                'dictionary_id' => $id,
                'is_removed' => true
            ]);

            $staging->executeStaging('core.procedure_action_dictionary', $payload);

            return $this->erpResponse(
                message: "Dictionary {$id} ready for delete."
            );

        }, 'Failed to delete Dictionary.');
    }
}
