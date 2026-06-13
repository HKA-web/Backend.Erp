<?php

namespace Modules\Authentication\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Http\Requests\UserRequest;

class UserDraftController extends Controller
{
    public function __construct(protected readonly BaseService $baseService) {}

    public function index()
    {
        return $this->erpExecution(function () {
            // Membaca langsung dari tabel temporary
            $query = DB::table('temporary.authentication_user');

            return $this->erpResponse($query, cache: false);
        });
    }

    public function store(UserRequest $request)
    {
        return $this->erpExecution(function () use ($request) {
            $this->baseService->executeProcedure('authentication.procedure_upsert_user_draft', $request->validated());

            return $this->erpResponse(
                message: 'Draft User saved successfully.'
            );
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $draft = DB::table('temporary.authentication_user')
                ->where('user_id', $id);

            return $this->erpResponse($draft, cache: false);
        });
    }

    public function update(UserRequest $request, $id)
    {
        return $this->erpExecution(function () use ($request, $id) {
            $payload = array_merge($request->validated(), ['user_id' => $id]);

            $this->baseService->executeProcedure('authentication.procedure_upsert_user_draft', $payload);

            return $this->erpResponse(message: 'Draft updated.');
        });
    }

    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {
            DB::table('temporary.authentication_user')
                ->where('user_id', $id)
                ->delete();

            return $this->erpResponse(message: 'Draft discarded.');
        });
    }

    public function commit($id)
    {
        return $this->erpExecution(function () use ($id) {
            $payload = ['user_id' => $id];

            $this->baseService->executeProcedure('authentication.procedure_commit_user', $payload, User::class);

            return $this->erpResponse(
                message: 'User committed to master successfully.'
            );
        }, 'Failed to commit draft.');
    }
}
