<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseService;
use Modules\Core\Models\Option;

class OptionController extends Controller
{
    public function __construct(protected readonly BaseService $baseService) {}

    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(Option::query(), tags: ['core.option']);
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = Option::where('option_id', $id);

            return $this->erpResponse($query, tags: ['core.option']);
        });
    }

    public function revise($id)
    {
        return $this->erpExecution(function () use ($id) {

            $this->baseService->executeProcedure('core.procedure_revise_option', [
                'option_id' => $id,
            ], Option::class);

                        

            return $this->erpResponse(
                message: "Option {$id} has been moved to drafts for revision."
            );
        }, 'Failed to initiate revision for Option.');
    }

    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {

            $this->baseService->requestDelete(Option::class, $id, 'core.procedure_revise_option');

                        

            return $this->erpResponse(
                message: "Delete request for Option {$id} processed according to model policy."
            );
        }, 'Failed to process delete request for Option.');
    }
}
