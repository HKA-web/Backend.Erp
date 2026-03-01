<?php

namespace Modules\Authentication\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Modules\Authentication\Http\Requests\UserRequest;
use Illuminate\Support\Facades\DB;

class UserDraftController extends Controller
{
    /**
     * Display all active drafts in the temporary table for current session.
     */
    public function index()
    {
        return $this->erpExecution(function () {
            // Membaca langsung dari tabel temporary
            $query = DB::table('temporary.authentication_user');
            return $this->erpResponse($query);
        });
    }

    /**
     * Save a new draft.
     */
    public function store(UserRequest $request, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request) {
            $staging->executeStaging('authentication.procedure_upsert_user_draft', $request->validated());

            return $this->erpResponse(
                message: "Draft User saved successfully."
            );
        });
    }

    /**
     * Show draft details from temporary table.
     */
    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $draft = DB::table('temporary.authentication_user')
                ->where('user_id', $id);

            return $this->erpResponse($draft);
        });
    }

    /**
     * Update existing draft in temporary table.
     */
    public function update(UserRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {
            $payload = array_merge($request->validated(), ['user_id' => $id]);

            $staging->executeStaging('authentication.procedure_upsert_user_draft', $payload);

            return $this->erpResponse(message: "Draft updated.");
        });
    }

    /**
     * Discard draft (Delete from temporary).
     */
    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {
            DB::table('temporary.authentication_user')
                ->where('user_id', $id)
                ->delete();

            return $this->erpResponse(message: "Draft discarded.");
        });
    }

    /**
     * Final Action: Commit Draft to Master.
     * POST /v1/user-drafts/{id}/commit
     */
    public function commit($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $id) {
            $payload = ['user_id' => $id];

            $staging->executeStaging('authentication.procedure_commit_user', $payload);

            return $this->erpResponse(
                message: "User committed to master successfully."
            );
        }, "Failed to commit draft.");
    }
}
