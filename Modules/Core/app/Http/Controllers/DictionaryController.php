<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseService;
use Modules\Core\Models\Dictionary;

class DictionaryController extends Controller
{
    public function __construct(protected readonly BaseService $baseService) {}

    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(Dictionary::query(), tags: ['core.dictionary']);
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = Dictionary::where('dictionary_id', $id);

            return $this->erpResponse($query, tags: ['core.dictionary']);
        });
    }

    public function revise($id)
    {
        return $this->erpExecution(function () use ($id) {

            $this->baseService->executeProcedure('core.procedure_revise_dictionary', [
                'dictionary_id' => $id,
            ], Dictionary::class);

                        

            return $this->erpResponse(
                message: "Dictionary {$id} has been moved to drafts for revision."
            );
        }, 'Failed to initiate revision for Dictionary.');
    }

    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {

            $this->baseService->requestDelete(Dictionary::class, $id, 'core.procedure_revise_dictionary');

                        

            return $this->erpResponse(
                message: "Delete request for Dictionary {$id} processed according to model policy."
            );
        }, 'Failed to process delete request for Dictionary.');
    }
}
