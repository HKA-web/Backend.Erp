<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Illuminate\Http\Request;
use Modules\Core\Http\Requests\CompanyRequest;
use Modules\Core\Models\Company;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource with ERP filtering & sorting.
     */
    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(Company::query());
        });
    }

    /**
     * Store a newly created resource in storage (Draft or Posted).
     */
    public function store(CompanyRequest $request, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request) {

            $staging->executeStaging('core.procedure_action_company', $request->validated());

            return $this->erpResponse(
                message: "Company {$request->company_name} Successfully Processed."
            );

        }, 'Failed to process Company.');
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = Company::where('company_id', $id);
            return $this->erpResponse($query);
        });
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CompanyRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {

            $validated = $request->validated();

            $payload = array_merge($validated, [
                'company_id' => $id
            ]);

            $staging->executeStaging('core.procedure_action_company', $payload);

            return $this->erpResponse(
                message: "Company {$id} ready for editing."
            );

        }, 'Failed to update Company.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CompanyRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {

            $validated = $request->validated();

            $payload = array_merge($validated, [
                'company_id' => $id,
                'is_removed' => true
            ]);

            $staging->executeStaging('core.procedure_action_company', $payload);

            return $this->erpResponse(
                message: "Company {$id} ready for delete."
            );

        }, 'Failed to delete Company.');
    }
}
