<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ResetPasswordAction;
use App\Concerns\ApiResponse;
use App\DataTransferObjects\Auth\ResetPasswordData;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;

class ResetPasswordController
{
    use ApiResponse;

    /**
     * @throws \CuyZ\Valinor\Mapper\MappingError|\Throwable
     */
    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        $message = ResetPasswordAction::resolve()->execute(
            data: ResetPasswordData::resolve($request->validated())
        );

        return $this->resolveSuccessResponse(message: __($message));
    }
}
