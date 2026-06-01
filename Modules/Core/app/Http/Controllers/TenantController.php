<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;

class TenantController extends Controller
{
    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(Tenant::query(), tags: ['tenant']);
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = Tenant::where('id', $id);

            return $this->erpResponse($query, tags: ['tenant']);
        });
    }
}
