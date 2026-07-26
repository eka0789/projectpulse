<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Project::with(['client', 'creator'])->withCount('tasks');

        if ($user->isMember()) {
            $query->whereHas('tasks', function ($q) use ($user) {
                $q->where('assignee_id', $user->id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('deadline_from')) {
            $query->where('deadline', '>=', $request->deadline_from);
        }

        if ($request->filled('deadline_to')) {
            $query->where('deadline', '<=', $request->deadline_to);
        }

        $projects = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Projects retrieved successfully.',
            'data' => $projects->items(),
            'meta' => [
                'current_page' => $projects->currentPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
                'last_page' => $projects->lastPage(),
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
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'client_brief' => 'nullable|string',
            'start_date' => 'nullable|date',
            'deadline' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:draft,active,on_hold,completed,cancelled',
        ]);

        $project = Project::create([
            ...$request->only(['client_id', 'name', 'description', 'client_brief', 'start_date', 'deadline', 'status']),
            'created_by' => $request->user()->id,
        ]);

        $project->load('client');

        return response()->json([
            'success' => true,
            'message' => 'Project created successfully.',
            'data' => $project,
            'meta' => null,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $project = Project::with(['client', 'creator', 'tasks.assignee'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Project retrieved successfully.',
            'data' => $project,
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

        $project = Project::findOrFail($id);

        $request->validate([
            'client_id' => 'sometimes|required|exists:clients,id',
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'client_brief' => 'nullable|string',
            'start_date' => 'nullable|date',
            'deadline' => 'sometimes|required|date',
            'status' => 'sometimes|required|in:draft,active,on_hold,completed,cancelled',
        ]);

        $project->update($request->only(['client_id', 'name', 'description', 'client_brief', 'start_date', 'deadline', 'status']));
        $project->load('client');

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully.',
            'data' => $project,
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

        $project = Project::findOrFail($id);
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully.',
            'data' => null,
            'meta' => null,
        ]);
    }
}
