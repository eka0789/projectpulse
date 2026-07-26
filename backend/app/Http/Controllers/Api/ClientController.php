<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null]
            ], 403);
        }

        $query = Client::withCount('projects');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('company')) {
            $query->where('company', 'like', "%{$request->company}%");
        }

        $clients = $query->orderBy('name')->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Clients retrieved successfully.',
            'data' => $clients->items(),
            'meta' => [
                'current_page' => $clients->currentPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
                'last_page' => $clients->lastPage(),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null]
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $client = Client::create([
            ...$request->only(['name', 'company', 'email', 'phone', 'address', 'notes']),
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Client created successfully.',
            'data' => $client,
            'meta' => null,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null]
            ], 403);
        }

        $client = Client::with('projects')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Client retrieved successfully.',
            'data' => $client,
            'meta' => null,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null]
            ], 403);
        }

        $client = Client::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'company' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $client->update($request->only(['name', 'company', 'email', 'phone', 'address', 'notes']));

        return response()->json([
            'success' => true,
            'message' => 'Client updated successfully.',
            'data' => $client,
            'meta' => null,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null]
            ], 403);
        }

        $client = Client::findOrFail($id);

        if ($client->projects()->where('status', 'active')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete client with active projects.',
                'error' => ['code' => 'CONFLICT', 'details' => null]
            ], 409);
        }

        $client->delete();

        return response()->json([
            'success' => true,
            'message' => 'Client soft deleted successfully.',
            'data' => null,
            'meta' => null,
        ]);
    }
}
