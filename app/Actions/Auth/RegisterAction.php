<?php

namespace App\Actions\Auth;

use App\DataTransferObjects\Auth\RegisterData;
use App\Models\User;
use Holiq\ActionData\Foundation\Action;
use Illuminate\Auth\Events\Registered;

readonly class RegisterAction extends Action
{
    /**
     * @return array<string, mixed>
     */
    public function execute(RegisterData $data): array
    {
        $user = User::query()->create($data->toArray());

        event(new Registered($user));

        $token = $user->createToken($user->email)->plainTextToken;

        return compact('user', 'token');
    }
}
