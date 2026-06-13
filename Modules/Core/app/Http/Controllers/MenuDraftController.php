<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Requests\MenuRequest;
use Modules\Core\Models\Menu;

class MenuDraftController extends Controller
{
    public function __construct(protected readonly BaseService $baseService) {}

    public function index()
    {
        return $this->erpExecution(function () {
            $query = DB::table('temporary.core_menu');

            return $this->erpResponse($query, cache: false);
        });
    }

    public function store(MenuRequest $request)
    {
        return $this->erpExecution(function () use ($request) {
            $this->baseService->executeProcedure('core.procedure_upsert_menu_draft', $request->validated());

            return $this->erpResponse(
                message: 'Draft Menu saved successfully.'
            );
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $draft = DB::table('temporary.core_menu')
                ->where('temporary_id', $id);

            return $this->erpResponse($draft, cache: false);
        });
    }

    public function update(MenuRequest $request, $id)
    {
        return $this->erpExecution(function () use ($request, $id) {
            $payload = array_merge($request->validated(), ['temporary_id' => $id]);

            $this->baseService->executeProcedure('core.procedure_upsert_menu_draft', $payload);

            return $this->erpResponse(message: 'Draft updated.');
        });
    }

    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {
            DB::table('temporary.core_menu')
                ->where('temporary_id', $id)
                ->delete();

            return $this->erpResponse(message: 'Draft discarded.');
        });
    }

    public function commit($id)
    {
        return $this->erpExecution(function () use ($id) {
            $payload = [
                'temporary_id' => $id,
                'menu_id' => request()->input('menu_id'),
            ];

            $this->baseService->executeProcedure('core.procedure_commit_menu', $payload, Menu::class);

                        

            return $this->erpResponse(
                message: 'Menu committed to master successfully.',
                tags: ['menu']
            );
        }, 'Failed to commit draft.');
    }
}
