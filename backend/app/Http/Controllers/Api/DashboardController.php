<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $service = new DashboardService;
        $summary = $service->getSummary($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Dashboard summary retrieved successfully.',
            'data' => $summary,
            'meta' => null,
        ]);
    }
}
