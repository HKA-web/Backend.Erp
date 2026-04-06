<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Modules\Core\Models\Menu;

class MenuController extends Controller
{
    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(Menu::query(), tags: ['menu']);
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = Menu::where('menu_id', $id);
            return $this->erpResponse($query, tags: ['menu']);
        });
    }

    public function revise($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($staging, $id) {

            $staging->executeStaging('core.procedure_revise_menu', [
                'menu_id' => $id
            ]);

            return $this->erpResponse(
                message: "Menu {$id} has been moved to drafts for revision."
            );
        }, "Failed to initiate revision for Menu.");
    }

    public function destroy($id, BaseStaging $staging)
    {
        return $this->erpExecution(function () use ($id, $staging) {

            $staging->requestDelete(Menu::class, $id, 'core.procedure_revise_menu');

            return $this->erpResponse(
                message: "Delete request for Menu {$id} processed according to model policy."
            );
        }, "Failed to process delete request for Menu.");
    }
}
