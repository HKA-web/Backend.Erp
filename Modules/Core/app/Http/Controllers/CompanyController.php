<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseService;
use Modules\Core\Models\Company;

class CompanyController extends Controller
{
    public function __construct(protected readonly BaseService $baseService) {}

    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(Company::query(), tags: ['core.company']);
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = Company::where('company_id', $id);

            return $this->erpResponse($query, tags: ['core.company']);
        });
    }

    public function revise($id)
    {
        return $this->erpExecution(function () use ($id) {

            $this->baseService->executeProcedure('core.procedure_revise_company', [
                'company_id' => $id,
            ], Company::class);

                        

            return $this->erpResponse(
                message: "Company {$id} has been moved to drafts for revision."
            );
        }, 'Failed to initiate revision for Company.');
    }

    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {

            $this->baseService->requestDelete(Company::class, $id, 'core.procedure_revise_company');

                        

            return $this->erpResponse(
                message: "Delete request for Company {$id} processed according to model policy."
            );
        }, 'Failed to process delete request for Company.');
    }
}
