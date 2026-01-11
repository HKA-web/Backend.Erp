<?php

namespace App\Actions\Auth;

use App\DataTransferObjects\Auth\ResetPasswordData;
use App\Models\User;
use Holiq\ActionData\Foundation\Action;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

readonly class ResetPasswordAction extends Action
{
    /**
     * @throws \Throwable
     */
    public function execute(ResetPasswordData $data): string
    {
        /** @var string $status */
        $status = Password::reset(
            credentials: $data->toArray(),
            callback: function (User $user) use ($data) {
                $user->forceFill([
                    'password' => Hash::make($data->password),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        throw_if(
            condition: $status != Password::PASSWORD_RESET,
            exception: ValidationException::withMessages(['email' => __($status)])
        );

        return $status;
    }
}
