<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use App\Services\CacheService;
use Modules\Core\Models\City;

class CityController extends Controller
{
    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(City::query(), tags: ['core.city']);
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = City::where('city_id', $id);

            return $this->erpResponse($query, tags: ['core.city']);
        });
    }

    public function revise($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $id) {

            $staging->executeStaging('core.procedure_revise_city', [
                'city_id' => $id,
            ]);

            // Clear cache - auto scan relations & tenant aware
            $city = City::find($id);
            if ($city) {
                CacheService::clearCache($city);
            }

            return $this->erpResponse(
                message: "City {$id} has been moved to drafts for revision."
            );
        }, 'Failed to initiate revision for City.');
    }

    public function destroy($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($id, $staging) {

            $staging->requestDelete(City::class, $id, 'core.procedure_revise_city');

            // Clear cache - auto scan relations & tenant aware
            $city = City::find($id);
            if ($city) {
                CacheService::clearCache($city);
            }

            return $this->erpResponse(
                message: "Delete request for City {$id} processed according to model policy."
            );
        }, 'Failed to process delete request for City.');
    }
}
