<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\SendResetLinkAction;
use App\Concerns\ApiResponse;
use App\Http\Requests\Auth\PasswordResetRequest;
use Illuminate\Http\JsonResponse;

class PasswordResetLinkController
{
    use ApiResponse;

    /**
     * @throws \Throwable
     */
    public function __invoke(PasswordResetRequest $request): JsonResponse
    {
        $message = SendResetLinkAction::resolve()->execute($request->validated());

        return $this->resolveSuccessResponse(message: __($message));
    }
}
