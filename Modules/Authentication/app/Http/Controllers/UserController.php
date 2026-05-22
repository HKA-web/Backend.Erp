<?php

namespace Modules\Authentication\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Illuminate\Http\Request;
use Modules\Authentication\Models\User;
use OpenApi\Annotations as OA;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/authentication/users",
     *     tags={"Users"},
     *     summary="Get all users",
     *     description="Retrieve a list of all users",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(User::query(), tags: ['user']);
        });
    }

    /**
     * @OA\Get(
     *     path="/authentication/users/{id}",
     *     tags={"Users"},
     *     summary="Get user by ID",
     *     description="Retrieve a specific user by ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = User::where('user_id', $id);

            return $this->erpResponse($query, tags: ['user']);
        });
    }

    /**
     * @OA\Post(
     *     path="/authentication/users/{id}/revise",
     *     tags={"Users"},
     *     summary="Move user to revision",
     *     description="Move a user to drafts for revision",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function revise($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $id) {

            $staging->executeStaging('authentication.procedure_revise_user', [
                'user_id' => $id,
            ]);

            return $this->erpResponse(
                message: "User {$id} has been moved to drafts for revision."
            );
        }, 'Failed to initiate revision for User.');
    }

    /**
     * @OA\Delete(
     *     path="/authentication/users/{id}",
     *     tags={"Users"},
     *     summary="Delete user",
     *     description="Process delete request for a user",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($id, $staging) {

            $staging->requestDelete(User::class, $id, 'authentication.procedure_revise_user');

            return $this->erpResponse(
                message: "Delete request for User {$id} processed according to model policy."
            );
        }, 'Failed to process delete request for User.');
    }

    /**
     * @OA\Post(
     *     path="/authentication/users/{id}/reorder",
     *     tags={"Users"},
     *     summary="Reorder user menus",
     *     description="Update menu order for a user",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"menus"},
     *             @OA\Property(
     *                 property="menus",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 description="Array of menu IDs in desired order"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function reorder(Request $request, $id, BaseStaging $staging)
    {
        $validated = $request->validate([
            'menus' => 'required|array',
        ]);

        return $this->erpExecution(function () use ($staging, $id, $validated) {

            $staging->executeStaging('authentication.procedure_reorder_menu_user', [
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
