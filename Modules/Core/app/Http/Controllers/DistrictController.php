<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseService;
use Modules\Core\Models\District;

class DistrictController extends Controller
{
    public function __construct(protected readonly BaseService $baseService) {}

    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(District::query(), tags: ['core.district']);
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = District::where('district_id', $id);

            return $this->erpResponse($query, tags: ['core.district']);
        });
    }

    public function revise($id)
    {
        return $this->erpExecution(function () use ($id) {

            $this->baseService->executeProcedure('core.procedure_revise_district', [
                'district_id' => $id,
            ], District::class);

                        

            return $this->erpResponse(
                message: "District {$id} has been moved to drafts for revision."
            );
        }, 'Failed to initiate revision for District.');
    }

    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {

            $this->baseService->requestDelete(District::class, $id, 'core.procedure_revise_district');

                        

            return $this->erpResponse(
                message: "Delete request for District {$id} processed according to model policy."
            );
        }, 'Failed to process delete request for District.');
    }
}
