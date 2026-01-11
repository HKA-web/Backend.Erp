<?php

namespace App\Actions\Auth;

use Holiq\ActionData\Foundation\Action;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

readonly class SendResetLinkAction extends Action
{
    /**
     * @param  array<string, mixed>  $data
     *
     * @throws \Throwable
     */
    public function execute(array $data): string
    {
        $status = Password::sendResetLink($data);

        throw_if(
            condition: $status != Password::RESET_LINK_SENT,
            exception: ValidationException::withMessages(['email' => __($status)])
        );

        return $status;
    }
}
