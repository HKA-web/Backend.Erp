<?php

namespace Modules\Authentication\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Illuminate\Support\Facades\DB;
use Modules\Authentication\Http\Requests\UserRequest;

class UserDraftController extends Controller
{
    public function index()
    {
        return $this->erpExecution(function () {
            // Membaca langsung dari tabel temporary
            $query = DB::table('temporary.authentication_user');

            return $this->erpResponse($query);
        });
    }

    public function store(UserRequest $request, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request) {
            $staging->executeStaging('authentication.procedure_upsert_user_draft', $request->validated());

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

            return $this->erpResponse($draft);
        });
    }

    public function update(UserRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {
            $payload = array_merge($request->validated(), ['user_id' => $id]);

            $staging->executeStaging('authentication.procedure_upsert_user_draft', $payload);

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

    public function commit($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $id) {
            $payload = ['user_id' => $id];

            $staging->executeStaging('authentication.procedure_commit_user', $payload, ['user']);

            return $this->erpResponse(
                message: 'User committed to master successfully.'
            );
        }, 'Failed to commit draft.');
    }
}
