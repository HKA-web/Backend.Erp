<?php

namespace Modules\Authentication\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Authentication\Models\User;

class UserController extends Controller
{
    public function index()
    {
        return $this->erpExecution(function () {
            return $this->erpResponse(User::query());
        });
    }

    public function create()
    {
        return view('authentication::create');
    }

    public function store(Request $request) {}

    public function show($id)
    {
        return view('authentication::show');
    }

    public function edit($id)
    {
        return view('authentication::edit');
    }

    public function update(Request $request, $id) {}

    public function destroy($id) {}
}
