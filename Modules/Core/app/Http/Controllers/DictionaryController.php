<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Modules\Core\Models\Dictionary;

class DictionaryController extends Controller
{
    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(Dictionary::query(), tags: ['dictionary']);
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = Dictionary::where('dictionary_id', $id);

            return $this->erpResponse($query, tags: ['dictionary']);
        });
    }

    public function revise($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $id) {

            $staging->executeStaging('core.procedure_revise_dictionary', [
                'dictionary_id' => $id,
            ]);

            return $this->erpResponse(
                message: "Dictionary {$id} has been moved to drafts for revision."
            );
        }, 'Failed to initiate revision for Dictionary.');
    }

    public function destroy($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($id, $staging) {

            $staging->requestDelete(Dictionary::class, $id, 'core.procedure_revise_dictionary');

            return $this->erpResponse(
                message: "Delete request for Dictionary {$id} processed according to model policy."
            );
        }, 'Failed to process delete request for Dictionary.');
    }
}
