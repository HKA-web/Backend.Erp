<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\HandleEmailVerificationAction;
use App\Concerns\ApiResponse;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;

class VerifyEmailController
{
    use ApiResponse;

    /**
     * @throws \Throwable
     */
    public function __invoke(EmailVerificationRequest $request): JsonResponse
    {
        HandleEmailVerificationAction::resolve()->execute($request);

        return $this->resolveSuccessResponse(message: 'Email successfully verified.');
    }
}
