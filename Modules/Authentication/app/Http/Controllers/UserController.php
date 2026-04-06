<?php

namespace Modules\Authentication\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Modules\Authentication\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(User::query(), tags: ['user']);
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = User::where('user_id', $id);
            return $this->erpResponse($query, tags: ['user']);
        });
    }

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

    public function destroy($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($id, $staging) {

            $staging->requestDelete(User::class, $id, 'authentication.procedure_revise_user');

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

            $staging->executeStaging('authentication.procedure_reorder_menu_user', [
                'user_id'     => $id,
                'menus'       => json_encode($validated['menus']),
                'executed_by' => $id
            ]);

            return $this->erpResponse(
                message: "Menu order personal saved successfully.",
                tags: ['user']
            );
        }, "Failed to update menu order for User.");
    }
}
