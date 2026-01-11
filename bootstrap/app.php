<?php

use App\Exceptions\EmailAlreadyVerifiedException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(path: __DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (Throwable $e, Request $request) {
            if ($request->is(patterns: 'api/*')) {
                $status = match (true) {
                    $e instanceof NotFoundHttpException => Response::HTTP_NOT_FOUND,
                    $e instanceof AuthenticationException => Response::HTTP_UNAUTHORIZED,
                    $e instanceof ValidationException, $e instanceof EmailAlreadyVerifiedException => Response::HTTP_UNPROCESSABLE_ENTITY,
                    default => Response::HTTP_INTERNAL_SERVER_ERROR,
                };

                $debugEnable = app()->hasDebugModeEnabled();

                $message = (! $debugEnable && $status === Response::HTTP_INTERNAL_SERVER_ERROR)
                    ? 'Internal server error, cannot processed the request.'
                    : $e->getMessage();

                $errors = $debugEnable
                    ? ($e instanceof ValidationException ? $e->errors() : $e->getTrace())
                    : ($e instanceof ValidationException ? $e->errors() : []);

                return response()->json(
                    data: [
                        'status' => 'error',
                        'message' => $message,
                        'errors' => $errors,
                    ],
                    status: $status,
                );
            }

            return null;
        });
    })->create();
