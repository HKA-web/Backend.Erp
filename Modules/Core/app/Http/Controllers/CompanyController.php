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

            // Memanggil stored procedure hasil generate migration otomatis
            $staging->executeStaging('core.push_company', $request->validated());

            return $this->erpResponse(
                message: "Company {$request->company_name} Successfully Processed."
            );

        }, "Failed to process Company.");
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $data = Company::findOrFail($id);
            return $this->erpResponse($data);
        });
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CompanyRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request) {

            $staging->executeStaging('core.push_company', $request->validated());

            return $this->erpResponse(
                message: "Company updated successfully."
            );

        }, "Failed to update Company.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {
            Company::destroy($id);
            return $this->erpResponse(message: "Company deleted successfully.");
        });
    }
}
