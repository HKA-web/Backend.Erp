<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Modules\Core\Models\District;

class DistrictController extends Controller
{
    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(District::query(), tags: ['district']);
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = District::where('district_id', $id);
            return $this->erpResponse($query, tags: ['district']);
        });
    }

    public function revise($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $id) {

            $staging->executeStaging('core.procedure_revise_district', [
                'district_id' => $id
            ]);

            return $this->erpResponse(
                message: "District {$id} has been moved to drafts for revision."
            );
        }, "Failed to initiate revision for District.");
    }

    public function destroy($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($id, $staging) {

            $staging->requestDelete(District::class, $id, 'core.procedure_revise_district');

            return $this->erpResponse(
                message: "Delete request for District {$id} processed according to model policy."
            );
        }, "Failed to process delete request for District.");
    }
}
