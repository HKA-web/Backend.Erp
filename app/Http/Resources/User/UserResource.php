<?php

namespace App\Http\Resources\User;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class UserResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return array_merge([
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,
        ], $this->timestamps());
    }
}
