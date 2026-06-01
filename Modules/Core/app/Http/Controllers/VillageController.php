<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use App\Services\CacheService;
use Modules\Core\Models\Village;

class VillageController extends Controller
{
    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(Village::query(), tags: ['core.village']);
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = Village::where('village_id', $id);

            return $this->erpResponse($query, tags: ['core.village']);
        });
    }

    public function revise($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $id) {

            $staging->executeStaging('core.procedure_revise_village', [
                'village_id' => $id,
            ]);

            // Clear cache - auto scan relations & tenant aware
            $village = Village::find($id);
            if ($village) {
                CacheService::clearCache($village);
            }

            return $this->erpResponse(
                message: "Village {$id} has been moved to drafts for revision."
            );
        }, 'Failed to initiate revision for Village.');
    }

    public function destroy($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($id, $staging) {

            $staging->requestDelete(Village::class, $id, 'core.procedure_revise_village');

            // Clear cache - auto scan relations & tenant aware
            $village = Village::find($id);
            if ($village) {
                CacheService::clearCache($village);
            }

            return $this->erpResponse(
                message: "Delete request for Village {$id} processed according to model policy."
            );
        }, 'Failed to process delete request for Village.');
    }
}
