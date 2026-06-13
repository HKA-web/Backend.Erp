<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseService;
use Modules\Core\Models\City;

class CityController extends Controller
{
    public function __construct(protected readonly BaseService $baseService) {}

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

    public function revise($id)
    {
        return $this->erpExecution(function () use ($id) {

            $this->baseService->executeProcedure('core.procedure_revise_city', [
                'city_id' => $id,
            ], City::class);

                        

            return $this->erpResponse(
                message: "City {$id} has been moved to drafts for revision."
            );
        }, 'Failed to initiate revision for City.');
    }

    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {

            $this->baseService->requestDelete(City::class, $id, 'core.procedure_revise_city');

                        

            return $this->erpResponse(
                message: "Delete request for City {$id} processed according to model policy."
            );
        }, 'Failed to process delete request for City.');
    }
}
