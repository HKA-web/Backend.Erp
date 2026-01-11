<?php

namespace App\Http\Controllers\Auth;

use App\Concerns\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController
{
    use ApiResponse;

    public function __invoke(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        /** @var \Laravel\Sanctum\PersonalAccessToken|null $token */
        $token = $user->currentAccessToken();
        $token?->delete();

        return $this->resolveSuccessResponse(
            message: 'Logout successful',
        );
    }
}
