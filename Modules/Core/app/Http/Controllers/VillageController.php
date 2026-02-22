<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\BaseStaging;
use Illuminate\Http\Request;
use Modules\Core\Http\Requests\VillageRequest;
use Modules\Core\Models\Village;

class VillageController extends Controller
{
    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(Village::query());
        });
    }

    public function store(VillageRequest $request, BaseStaging $staging)
    {
        return $this->erpExec(function () use ($staging, $request) {

            $staging->executeStaging('core.push_village', $request->validated());

            return $this->erpResponse(
                message: "Village {$request->village_name} Successfully Commit."
            );

        }, "Failed to commit Village.");
    }

    public function show($id)
    {
        return view('core::show');
    }

    public function update(Request $request, $id) {}

    public function destroy($id) {}
}
