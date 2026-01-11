<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegisterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this['user'];

        return [
            'access' => $this['token'],
            'session' => [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'email' => $user->email,
                'real_name' => '-',
                'phone' => '-',
            ],
        ];
    }
}
