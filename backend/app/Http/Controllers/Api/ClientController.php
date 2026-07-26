<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
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

        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->string('created_from'));
        }

        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->string('created_to'));
        }

        $sort = in_array($request->string('sort')->toString(), ['name', 'company', 'created_at'], true)
            ? $request->string('sort')->toString()
            : 'name';
        $direction = $request->string('direction')->lower()->toString() === 'desc' ? 'desc' : 'asc';
        $perPage = max(1, min($request->integer('per_page', 15), 100));
        $clients = $query->orderBy($sort, $direction)->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Clients retrieved successfully.',
            'data' => ClientResource::collection($clients->getCollection()),
            'meta' => [
                'current_page' => $clients->currentPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
                'last_page' => $clients->lastPage(),
            ],
        ]);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
            ], 403);
        }

        $client = Client::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Client created successfully.',
            'data' => new ClientResource($client),
            'meta' => null,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
            ], 403);
        }

        $client = Client::with('projects')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Client retrieved successfully.',
            'data' => new ClientResource($client),
            'meta' => null,
        ]);
    }

    public function update(UpdateClientRequest $request, int $id): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
            ], 403);
        }

        $client = Client::findOrFail($id);

        $client->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Client updated successfully.',
            'data' => new ClientResource($client),
            'meta' => null,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
            ], 403);
        }

        $client = Client::findOrFail($id);

        if ($client->projects()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a client that still has projects.',
                'error' => ['code' => 'RESOURCE_CONFLICT', 'details' => null],
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
