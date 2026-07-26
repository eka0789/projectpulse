<?php

namespace App\Http\Controllers\Api;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Task;
use App\Services\AI\TaskBreakdownRequestData;
use App\Services\AI\TaskBreakdownService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Task::with(['project.client', 'assignee', 'creator'])
            ->withSum('timeLogs as total_logged_minutes', 'duration_minutes');

        if ($user->isMember()) {
            $query->where('assignee_id', $user->id);
        } elseif ($request->filled('assignee_id')) {
            $query->where('assignee_id', $request->assignee_id);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->boolean('overdue')) {
            $query->where('status', '!=', 'done')
                ->whereNotNull('deadline')
                ->where('deadline', '<', Carbon::today()->toDateString());
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }

        $tasks = $query->orderBy('updated_at', 'desc')->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Tasks retrieved successfully.',
            'data' => $tasks->items(),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
                'last_page' => $tasks->lastPage(),
            ]
        ]);
    }

    public function store(Request $request, ?int $projectId = null): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null]
            ], 403);
        }

        $targetProjectId = $projectId ?? $request->project_id;

        $request->validate([
            'project_id' => $projectId ? 'nullable' : 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:frontend,backend,design,qa,devops,management,other',
            'assignee_id' => 'nullable|exists:users,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:todo,in_progress,review,done',
            'estimated_hours' => 'nullable|numeric|min:0.1|max:500',
            'start_date' => 'nullable|date',
            'deadline' => 'nullable|date',
        ]);

        $task = Task::create([
            'project_id' => $targetProjectId,
            ...$request->only(['title', 'description', 'category', 'assignee_id', 'priority', 'status', 'estimated_hours', 'start_date', 'deadline']),
            'created_by' => $request->user()->id,
            'source' => 'manual',
        ]);

        if ($task->assignee_id) {
            $this->createAssignmentNotification($task, $task->assignee_id);
        }

        $task->load(['project', 'assignee']);

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully.',
            'data' => $task,
            'meta' => null,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $task = Task::with(['project.client', 'assignee', 'creator', 'timeLogs.user', 'progressNotes.user', 'comments.user'])
            ->withSum('timeLogs as total_logged_minutes', 'duration_minutes')
            ->findOrFail($id);

        if ($user->isMember() && $task->assignee_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null]
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Task retrieved successfully.',
            'data' => $task,
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

        $task = Task::findOrFail($id);
        $oldAssigneeId = $task->assignee_id;

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'sometimes|required|in:frontend,backend,design,qa,devops,management,other',
            'assignee_id' => 'nullable|exists:users,id',
            'priority' => 'sometimes|required|in:low,medium,high,urgent',
            'status' => 'sometimes|required|in:todo,in_progress,review,done',
            'estimated_hours' => 'nullable|numeric|min:0.1|max:500',
            'start_date' => 'nullable|date',
            'deadline' => 'nullable|date',
        ]);

        $task->update($request->only(['title', 'description', 'category', 'assignee_id', 'priority', 'status', 'estimated_hours', 'start_date', 'deadline']));

        if ($request->filled('assignee_id') && $request->assignee_id !== $oldAssigneeId) {
            $this->createAssignmentNotification($task, $request->assignee_id);
        }

        $task->load(['project', 'assignee']);

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully.',
            'data' => $task,
            'meta' => null,
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $task = Task::findOrFail($id);

        if ($user->isMember() && $task->assignee_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null]
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:todo,in_progress,review,done',
        ]);

        $currentStatus = TaskStatus::from($task->status);
        $targetStatus = TaskStatus::from($request->status);

        if (!$user->isAdmin() && !$currentStatus->isValidTransitionTo($targetStatus)) {
            return response()->json([
                'success' => false,
                'message' => "Invalid status transition from '{$task->status}' to '{$request->status}'.",
                'error' => [
                    'code' => 'INVALID_STATUS_TRANSITION',
                    'details' => null,
                ]
            ], 422);
        }

        $task->status = $targetStatus->value;
        if ($targetStatus === TaskStatus::DONE) {
            $task->completed_at = now();
        }
        $task->save();

        if ($task->assignee_id) {
            Notification::create([
                'id' => (string) Str::uuid(),
                'user_id' => $task->assignee_id,
                'type' => 'TaskStatusUpdated',
                'title' => 'Task Status Changed',
                'message' => "Task '{$task->title}' status updated to {$targetStatus->value}.",
                'data' => ['task_id' => $task->id, 'status' => $targetStatus->value],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Task status updated successfully.',
            'data' => $task,
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

        $task = Task::findOrFail($id);
        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully.',
            'data' => null,
            'meta' => null,
        ]);
    }

    public function generateAISuggestions(Request $request, int $projectId): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null]
            ], 403);
        }

        $project = Project::findOrFail($projectId);

        $request->validate([
            'brief' => 'required|string|min:10|max:10000',
            'preferences' => 'nullable|array',
        ]);

        $service = new TaskBreakdownService();
        $result = $service->generateTasks(new TaskBreakdownRequestData(
            brief: $request->brief,
            preferences: $request->preferences ?? [],
            projectId: $project->id,
            userId: $request->user()->id
        ));

        if (!$result->success) {
            return response()->json([
                'success' => false,
                'message' => $result->errorMessage ?? 'AI task suggestions are temporarily unavailable. You can continue by adding tasks manually.',
                'error' => [
                    'code' => $result->errorCode ?? 'AI_PROVIDER_UNAVAILABLE',
                    'details' => null,
                ]
            ], 503);
        }

        return response()->json([
            'success' => true,
            'message' => 'Task suggestions generated successfully.',
            'data' => [
                'generation_id' => $result->generationId,
                'provider' => $result->provider,
                'model' => $result->model,
                'tasks' => $result->tasks,
                'source' => $result->source,
            ],
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'latency_ms' => $result->latencyMs,
            ]
        ]);
    }

    public function bulkStore(Request $request, int $projectId): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null]
            ], 403);
        }

        $project = Project::findOrFail($projectId);

        $request->validate([
            'tasks' => 'required|array|min:1',
            'tasks.*.title' => 'required|string|max:255',
            'tasks.*.description' => 'nullable|string',
            'tasks.*.category' => 'required|in:frontend,backend,design,qa,devops,management,other',
            'tasks.*.priority' => 'required|in:low,medium,high,urgent',
            'tasks.*.estimated_hours' => 'nullable|numeric|min:0.5|max:500',
            'tasks.*.assignee_id' => 'nullable|exists:users,id',
            'tasks.*.deadline' => 'nullable|date',
            'tasks.*.source' => 'nullable|in:manual,ai',
        ]);

        $createdTasks = DB::transaction(function () use ($request, $project) {
            $items = [];
            foreach ($request->tasks as $raw) {
                $task = Task::create([
                    'project_id' => $project->id,
                    'title' => $raw['title'],
                    'description' => $raw['description'] ?? null,
                    'category' => $raw['category'],
                    'priority' => $raw['priority'],
                    'status' => 'todo',
                    'estimated_hours' => $raw['estimated_hours'] ?? 4.0,
                    'assignee_id' => $raw['assignee_id'] ?? null,
                    'deadline' => $raw['deadline'] ?? $project->deadline,
                    'created_by' => $request->user()->id,
                    'source' => $raw['source'] ?? 'ai',
                ]);

                if ($task->assignee_id) {
                    $this->createAssignmentNotification($task, $task->assignee_id);
                }

                $items[] = $task;
            }
            return $items;
        });

        return response()->json([
            'success' => true,
            'message' => count($createdTasks) . ' tasks created successfully.',
            'data' => $createdTasks,
            'meta' => null,
        ], 201);
    }

    private function createAssignmentNotification(Task $task, int $assigneeId): void
    {
        Notification::create([
            'id' => (string) Str::uuid(),
            'user_id' => $assigneeId,
            'type' => 'TaskAssigned',
            'title' => 'New Task Assigned',
            'message' => "You have been assigned to task: '{$task->title}'",
            'data' => ['task_id' => $task->id, 'project_id' => $task->project_id],
        ]);
    }
}
