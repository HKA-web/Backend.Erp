<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LoginAction;
use App\Concerns\ApiResponse;
use App\DataTransferObjects\Auth\LoginData;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Api\LoginResource;
use Illuminate\Http\JsonResponse;

class LoginController
{
    use ApiResponse;

    /**
     * @throws \CuyZ\Valinor\Mapper\MappingError|\Throwable
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $data = LoginAction::resolve()->execute(
            data: LoginData::resolve(data: $request->validated())
        );

        return $this->resolveSuccessResponse(
            message: 'Login successful',
            data: LoginResource::make($data),
        );
    }
}
