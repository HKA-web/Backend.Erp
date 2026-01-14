<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Http\JsonResponse;

trait HasTransactionResponse
{
    public function executeTransaction(callable $callback, string $successMessage = 'Operation successful'): JsonResponse
    {
        try {
            $result = DB::transaction(function () use ($callback) {
                return $callback();
            });

            return response()->json([
                'status'  => Str::studly('success'),
                'message' => $successMessage,
                'data'    => $result,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => Str::studly('error'),
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred during the operation.',
                'trace'   => config('app.debug') ? $e->getTrace() : null,
            ], 500);
        }
    }
}
