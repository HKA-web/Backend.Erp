<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Modules\Core\Models\Province;

class ProvinceController extends Controller
{
    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(Province::query(), tags: ['province']);
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = Province::where('province_id', $id);

            return $this->erpResponse($query, tags: ['province']);
        });
    }

    public function revise($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $id) {

            $staging->executeStaging('core.procedure_revise_province', [
                'province_id' => $id,
            ]);

            return $this->erpResponse(
                message: "Province {$id} has been moved to drafts for revision."
            );
        }, 'Failed to initiate revision for Province.');
    }

    public function destroy($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($id, $staging) {

            $staging->requestDelete(Province::class, $id, 'core.procedure_revise_province');

            return $this->erpResponse(
                message: "Delete request for Province {$id} processed according to model policy."
            );
        }, 'Failed to process delete request for Province.');
    }
}
