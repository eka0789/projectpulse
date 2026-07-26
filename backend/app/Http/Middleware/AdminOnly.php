<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'This action requires an administrator account.',
                'error' => [
                    'code' => 'FORBIDDEN',
                    'details' => null,
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
