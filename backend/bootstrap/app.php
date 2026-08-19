<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Ensure CORS headers are added to API responses (including errors like 401/403)
        $middleware->prepend(HandleCors::class);
        $middleware->statefulApi();
        // Active-role selection is stored in the authenticated browser
        // session and must survive requests to the API.
        $middleware->appendToGroup('api', \Illuminate\Session\Middleware\StartSession::class);

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'institution' => \App\Http\Middleware\CheckInstitution::class,
            'active.role' => \App\Http\Middleware\RequireActiveRole::class,
        ]);

        // Rate limiting: 60 requests per minute per user
        $middleware->throttleApi('60,1');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle custom validation exceptions
        $exceptions->render(function (\App\Exceptions\CustomValidationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => $e->getMessage(),
                        'errors' => $e->getErrors(),
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], $e->getStatusCode());
            }
        });

        // Handle resource not found exceptions
        $exceptions->render(function (\App\Exceptions\ResourceNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => $e->getMessage(),
                        'resource' => $e->getResource(),
                        'resource_id' => $e->getResourceId(),
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], $e->getStatusCode());
            }
        });

        // Handle unauthorized exceptions
        $exceptions->render(function (\App\Exceptions\UnauthorizedException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => $e->getMessage(),
                        'action' => $e->getAction(),
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], $e->getStatusCode());
            }
        });

        // Handle validation exceptions
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'The given data was invalid.',
                        'errors' => $e->errors(),
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 422);
            }
        });

        // Handle model not found exceptions
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                $model = class_basename($e->getModel());
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => "{$model} not found",
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 404);
            }
        });

        // Handle authentication exceptions
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHENTICATED',
                        'message' => 'Unauthenticated.',
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 401);
            }
        });

        // Handle authorization exceptions
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => $e->getMessage() ?: 'This action is unauthorized.',
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 403);
            }
        });

        // Handle general exceptions
        $exceptions->render(function (\Exception $e, $request) {
            if ($request->expectsJson()) {
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                $message = config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.';

                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'SERVER_ERROR',
                        'message' => $message,
                        ...(config('app.debug') ? ['trace' => $e->getTraceAsString()] : []),
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], $statusCode);
            }
        });
    })->create();
