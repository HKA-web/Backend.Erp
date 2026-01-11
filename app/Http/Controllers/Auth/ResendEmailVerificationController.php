<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ResendEmailVerificationAction;
use App\Concerns\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResendEmailVerificationController
{
    use ApiResponse;

    /**
     * @throws \Throwable
     */
    public function __invoke(Request $request): JsonResponse
    {
        ResendEmailVerificationAction::resolve()->execute($request);

        return $this->resolveSuccessResponse(message: 'Email verification link sent.');
    }
}
