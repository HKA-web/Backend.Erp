<?php

namespace App\Actions\Auth;

use App\Exceptions\EmailAlreadyVerifiedException;
use Holiq\ActionData\Foundation\Action;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

readonly class HandleEmailVerificationAction extends Action
{
    /**
     * @throws \Throwable
     */
    public function execute(Request $request): void
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        throw_if(condition: $user->hasVerifiedEmail(), exception: EmailAlreadyVerifiedException::class);

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }
    }
}
