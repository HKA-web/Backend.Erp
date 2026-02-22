<?php

namespace Modules\Authentication\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Authentication\Http\Requests\UserRequest;
use Modules\Authentication\Models\User;

class UserController extends Controller
{
    /**
     * Display a listing of the resource with ERP filtering & sorting.
     */
    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(User::query());
        });
    }

    /**
     * Store a newly created resource in storage (Draft or Posted).
     */
    protected static ?string $password;

    public function store(UserRequest $request, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request) {

            $validated = $request->validated();

            $payload = array_merge($validated, [
                'user_id' => $request->user_id ?? (string) Str::uuid(),
                'email_verified_at' => $request->email_verified_at ?? now(),
                'password' => Hash::make($request->password),
                'remember_token' => $request->remember_token,
            ]);

            $staging->executeStaging('authentication.procedure_action_user', $payload);

            return $this->erpResponse(
                message: "User {$request->user_name} Successfully Processed."
            );

        }, "Failed to process User.");
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = User::where('user_id', $id);
            return $this->erpResponse($query);
        });
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {

            $validated = $request->validated();

            $payload = array_merge($validated, [
                'user_id' => $id
            ]);

            $staging->executeStaging('authentication.procedure_action_user', $payload);

            return $this->erpResponse(
                message: "User {$id} ready for editing."
            );

        }, 'Failed to update User.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {

            $validated = $request->validated();

            $payload = array_merge($validated, [
                'user_id' => $id,
                'is_removed' => true
            ]);

            $staging->executeStaging('authentication.procedure_action_user', $payload);

            return $this->erpResponse(
                message: "User {$id} ready for delete."
            );

        }, 'Failed to delete User.');
    }
}
