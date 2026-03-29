<?php

namespace Modules\Authentication\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Modules\Authentication\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Display a listing of final posted resources.
     */
    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(User::query());
        });
    }

    /**
     * Show the specified final resource.
     */
    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = User::where('user_id', $id);
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

            $staging->executeStaging('authentication.procedure_revise_user', [
                'user_id' => $id
            ]);

            return $this->erpResponse(
                message: "User {$id} has been moved to drafts for revision."
            );
        }, "Failed to initiate revision for User.");
    }

    /**
     * Remove the specified resource (Encapsulated logic in BaseStaging).
     * DELETE /v1/{{model_plural_lower}}/{id}
     */
    public function destroy($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($id, $staging) {

            // Logika pengecekan trait & eksekusi SP ada di dalam sini
            $staging->requestDelete(User::class, $id, 'authentication.procedure_upsert_user_draft');

            return $this->erpResponse(
                message: "Delete request for User {$id} processed according to model policy."
            );

        }, "Failed to process delete request for User.");
    }

    public function reorder(Request $request, $id, BaseStaging $staging)
    {
        $validated = $request->validate([
            'menus' => 'required|array'
        ]);
        
        return $this->erpExecution(function () use ($staging, $id, $validated) {

            $user = User::findOrFail($id);
            $staging->executeStaging('authentication.procedure_quick_update_user', [
                'user_id'     => $id,
                'menus'       => $validated['menus'],
                'executed_by' => auth()->id() 
            ]);

            return $this->erpResponse(
                message: "Menu order personal saved successfully."
            );

        }, "Failed to update menu order for User.");
    }

}
