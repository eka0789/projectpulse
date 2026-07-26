<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_active === false) {
            return new JsonResponse([
                'success' => false,
                'message' => 'This account is inactive.',
                'error' => [
                    'code' => 'FORBIDDEN',
                    'details' => null,
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
