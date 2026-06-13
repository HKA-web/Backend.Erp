<?php

namespace Modules\Authentication\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseService;
use Illuminate\Http\Request;
use Modules\Authentication\Models\User;

class UserController extends Controller
{
    public function __construct(protected readonly BaseService $baseService) {}


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

    public function revise($id)
    {
        return $this->erpExecution(function () use ($id) {

            $this->baseService->executeProcedure('authentication.procedure_revise_user', [
                'user_id' => $id,
            ], User::class);

            return $this->erpResponse(
                message: "User {$id} has been moved to drafts for revision."
            );
        }, 'Failed to initiate revision for User.');
    }

    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {

            $this->baseService->requestDelete(User::class, $id, 'authentication.procedure_revise_user');

            return $this->erpResponse(
                message: "Delete request for User {$id} processed according to model policy."
            );
        }, 'Failed to process delete request for User.');
    }

    public function reorder(Request $request, $id)
    {
        $validated = $request->validate([
            'menus' => 'required|array',
        ]);

        return $this->erpExecution(function () use ($id, $validated) {

            $this->baseService->executeProcedure('authentication.procedure_reorder_menu_user', [
                'user_id' => $id,
                'menus' => json_encode($validated['menus']),
                'executed_by' => $id,
            ]);

            return $this->erpResponse(
                message: 'Menu order personal saved successfully.',
                tags: ['user']
            );
        }, 'Failed to update menu order for User.');
    }
}
