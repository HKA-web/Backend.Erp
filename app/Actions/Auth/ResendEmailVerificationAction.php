<?php

namespace App\Actions\Auth;

use App\Exceptions\EmailAlreadyVerifiedException;
use Holiq\ActionData\Foundation\Action;
use Illuminate\Http\Request;

readonly class ResendEmailVerificationAction extends Action
{
    /**
     * @throws \Throwable
     */
    public function execute(Request $request): void
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        throw_if(condition: $user->hasVerifiedEmail(), exception: EmailAlreadyVerifiedException::class);

        $user->sendEmailVerificationNotification();
    }
}
