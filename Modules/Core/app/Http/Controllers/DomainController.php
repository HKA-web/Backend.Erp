<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Stancl\Tenancy\Database\Models\Domain;

class DomainController extends Controller
{
    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(Domain::query(), tags: ['domain']);
        });
    }

    public function show($id)
    {
        return $this->erpExecution(function () use ($id) {
            $query = Domain::where('id', $id);

            return $this->erpResponse($query, tags: ['domain']);
        });
    }
}
