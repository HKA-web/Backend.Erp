<?php

namespace App\Actions\Auth;

use App\DataTransferObjects\Auth\LoginData;
use App\Models\User;
use Holiq\ActionData\Foundation\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

readonly class LoginAction extends Action
{
    /**
     * @return array<string, mixed>
     *
     * @throws \Throwable
     */
    public function execute(LoginData $data): array
    {
        throw_unless(
            condition: Auth::attempt($data->toArray()),
            exception: ValidationException::withMessages([
                'email' => trans(key: 'auth.failed'),
            ])
        );

        /** @var User $user */
        $user = User::query()->where(column: 'email', operator: $data->email)->first();

        $token = $user->createToken(name: $user->email)->plainTextToken;

        return compact('user', 'token');
    }
}
