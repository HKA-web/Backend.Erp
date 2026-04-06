<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Modules\Core\Http\Requests\MenuRequest;
use Illuminate\Support\Facades\DB;

class MenuDraftController extends Controller
{
    public function index()
    {
        return $this->erpExecution(function () {
            $query = DB::table('temporary.core_menu');
            return $this->erpResponse($query);
        });
    }

    public function store(MenuRequest $request, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request) {
            $staging->executeStaging('core.procedure_upsert_menu_draft', $request->validated());

            return $this->erpResponse(
                message: "Draft Menu saved successfully."
            );
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $draft = DB::table('temporary.core_menu')
                ->where('temporary_id', $id);

            return $this->erpResponse($draft);
        });
    }

    public function update(MenuRequest $request, $id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $request, $id) {
            $payload = array_merge($request->validated(), ['temporary_id' => $id]);

            $staging->executeStaging('core.procedure_upsert_menu_draft', $payload);

            return $this->erpResponse(message: "Draft updated.");
        });
    }

    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {
            DB::table('temporary.core_menu')
                ->where('temporary_id', $id)
                ->delete();

            return $this->erpResponse(message: "Draft discarded.");
        });
    }

    public function commit($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $id) {
            $payload = [
                'temporary_id' => $id,
                'menu_id'  => request()->input('menu_id')
            ];

            $staging->executeStaging('core.procedure_commit_menu', $payload);

            return $this->erpResponse(
                message: "Menu committed to master successfully.",
                tags: ['menu']
            );
        }, "Failed to commit draft.");
    }
}
