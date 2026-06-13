<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseService;
use Modules\Core\Models\Menu;

class MenuController extends Controller
{
    public function __construct(protected readonly BaseService $baseService) {}

    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(Menu::query(), tags: ['core.menu']);
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = Menu::where('menu_id', $id);

            return $this->erpResponse($query, tags: ['core.menu']);
        });
    }

    public function revise($id)
    {
        return $this->erpExecution(function () use ($id) {

            $this->baseService->executeProcedure('core.procedure_revise_menu', [
                'menu_id' => $id,
            ], Menu::class);

                        

            return $this->erpResponse(
                message: "Menu {$id} has been moved to drafts for revision."
            );
        }, 'Failed to initiate revision for Menu.');
    }

    public function destroy($id)
    {
        return $this->erpExecution(function () use ($id) {

            $this->baseService->requestDelete(Menu::class, $id, 'core.procedure_revise_menu');

                        

            return $this->erpResponse(
                message: "Delete request for Menu {$id} processed according to model policy."
            );
        }, 'Failed to process delete request for Menu.');
    }
}
