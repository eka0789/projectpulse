<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function liveness(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'projectpulse-backend',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function readiness(): JsonResponse
    {
        try {
            DB::connection()->getPdo();

            return response()->json([
                'status' => 'ready',
                'database' => 'connected',
                'service' => 'projectpulse-backend',
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Throwable) {
            return response()->json([
                'status' => 'error',
                'database' => 'disconnected',
                'message' => 'A required dependency is unavailable.',
            ], 503);
        }
    }
}
