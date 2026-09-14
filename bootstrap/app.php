<?php

use App\Application\Auth\Exceptions\EmailAlreadyRegisteredException;
use App\Http\Middleware\AssignRequestId;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AssignRequestId::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_FAILED',
                    'message' => 'The supplied data is invalid.',
                    'details' => $exception->errors(),
                ],
                'meta' => [
                    'request_id' => $request->attributes->get('request_id'),
                ],
            ], $exception->status);
        });

        $exceptions->render(function (EmailAlreadyRegisteredException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'EMAIL_ALREADY_REGISTERED',
                    'message' => $exception->getMessage(),
                ],
                'meta' => [
                    'request_id' => $request->attributes->get('request_id'),
                ],
            ], 422);
        });
    })->create();
