<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Modules\Core\Models\Dictionary;
use Illuminate\Http\Request;

class DictionaryController extends Controller
{
    /**
     * Display a listing of final posted resources.
     */
    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(Dictionary::query());
        });
    }

    /**
     * Show the specified final resource.
     */
    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = Dictionary::where('dictionary_id', $id);
            return $this->erpResponse($query);
        });
    }

    /**
     * Action to pull Master data into Draft for revision.
     * POST /v1/{{model_plural_lower}}/{id}/revise
     */
    public function revise($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $id) {

            $staging->executeStaging('core.procedure_revise_dictionary', [
                'dictionary_id' => $id
            ]);

            return $this->erpResponse(
                message: "Dictionary {$id} has been moved to drafts for revision."
            );
        }, "Failed to initiate revision for Dictionary.");
    }

    /**
     * Remove the specified resource (Encapsulated logic in BaseStaging).
     * DELETE /v1/{{model_plural_lower}}/{id}
     */
    public function destroy($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($id, $staging) {

            // Logika pengecekan trait & eksekusi SP ada di dalam sini
            $staging->requestDelete(Dictionary::class, $id, 'core.procedure_upsert_dictionary_draft');

            return $this->erpResponse(
                message: "Delete request for Dictionary {$id} processed according to model policy."
            );

        }, "Failed to process delete request for Dictionary.");
    }
}
