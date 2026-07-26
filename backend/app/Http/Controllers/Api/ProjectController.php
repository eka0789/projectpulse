<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $sort = in_array($request->string('sort')->toString(), ['name', 'status', 'deadline', 'created_at'], true)
            ? $request->string('sort')->toString()
            : 'created_at';
        $direction = in_array($request->string('direction')->lower()->toString(), ['asc', 'desc'], true)
            ? $request->string('direction')->lower()->toString()
            : 'desc';
        $perPage = max(1, min($request->integer('per_page', 15), 100));
        $projects = $query->orderBy($sort, $direction)->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Projects retrieved successfully.',
            'data' => ProjectResource::collection($projects->getCollection()),
            'meta' => [
                'current_page' => $projects->currentPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
                'last_page' => $projects->lastPage(),
            ],
        ]);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
            ], 403);
        }

        $project = Project::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        $project->load('client');

        return response()->json([
            'success' => true,
            'message' => 'Project created successfully.',
            'data' => new ProjectResource($project),
            'meta' => null,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $project = Project::with(['client', 'creator', 'tasks.assignee'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Project retrieved successfully.',
            'data' => new ProjectResource($project),
            'meta' => null,
        ]);
    }

    public function update(UpdateProjectRequest $request, int $id): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
            ], 403);
        }

        $project = Project::findOrFail($id);

        $project->update($request->validated());
        $project->load('client');

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully.',
            'data' => new ProjectResource($project),
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

        $project = Project::findOrFail($id);
        DB::transaction(function () use ($project): void {
            $project->tasks()->delete();
            $project->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully.',
            'data' => null,
            'meta' => null,
        ]);
    }
}
