<?php

use App\Exceptions\StaleModelException;
use App\Http\Middleware\AddRequestId;
use App\Http\Middleware\AdminOnly;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [AddRequestId::class]);
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'admin' => AdminOnly::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $exception): bool => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (ValidationException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'details' => $exception->errors(),
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Authentication is required.',
                'error' => ['code' => 'UNAUTHENTICATED', 'details' => null],
            ], Response::HTTP_UNAUTHORIZED);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to perform this action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
            ], Response::HTTP_FORBIDDEN);
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'The requested resource was not found.',
                'error' => ['code' => 'RESOURCE_NOT_FOUND', 'details' => null],
            ], Response::HTTP_NOT_FOUND);
        });

        $exceptions->render(function (ThrottleRequestsException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'error' => ['code' => 'RATE_LIMIT_EXCEEDED', 'details' => null],
            ], Response::HTTP_TOO_MANY_REQUESTS);
        });

        $exceptions->render(function (StaleModelException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'error' => [
                    'code' => 'STALE_RESOURCE',
                    'details' => [
                        'resource' => $exception->resource,
                        'id' => $exception->resourceId,
                    ],
                ],
            ], Response::HTTP_CONFLICT);
        });

        $exceptions->render(function (Throwable $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/*') || config('app.debug')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred.',
                'error' => ['code' => 'INTERNAL_SERVER_ERROR', 'details' => null],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    })->create();
