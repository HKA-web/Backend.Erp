<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Modules\Core\Http\Requests\DictionaryRequest;
use Illuminate\Support\Facades\DB;

class DictionaryDraftController extends Controller
{
    /**
     * Display all active drafts in the temporary table for current session.
     */
    public function index()
    {
        return $this->erpExecution(function () {
            // Membaca langsung dari tabel temporary
            $query = DB::table('temporary.core_dictionary');
            return $this->erpResponse($query);
        });
    }

    /**
     * Save a new draft.
     */
    public function store(DictionaryRequest $request, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request) {
            $staging->executeStaging('core.procedure_upsert_dictionary_draft', $request->validated());

            return $this->erpResponse(
                message: "Draft Dictionary saved successfully."
            );
        });
    }

    /**
     * Show draft details from temporary table.
     */
    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $draft = DB::table('temporary.core_dictionary')
                ->where('dictionary_id', $id);

            return $this->erpResponse($draft);
        });
    }

    /**
     * Update existing draft in temporary table.
     */
    public function update(DictionaryRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {
            $payload = array_merge($request->validated(), ['dictionary_id' => $id]);

            $staging->executeStaging('core.procedure_upsert_dictionary_draft', $payload);

            return $this->erpResponse(message: "Draft updated.");
        });
    }

    /**
     * Discard draft (Delete from temporary).
     */
    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {
            DB::table('temporary.core_dictionary')
                ->where('dictionary_id', $id)
                ->delete();

            return $this->erpResponse(message: "Draft discarded.");
        });
    }

    /**
     * Final Action: Commit Draft to Master.
     * POST /v1/dictionary-drafts/{id}/commit
     */
    public function commit($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $id) {
            $payload = ['dictionary_id' => $id];

            $staging->executeStaging('core.procedure_commit_dictionary', $payload);

            return $this->erpResponse(
                message: "Dictionary committed to master successfully."
            );
        }, "Failed to commit draft.");
    }
}
