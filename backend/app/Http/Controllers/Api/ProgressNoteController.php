<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProgressNote\StoreProgressNoteRequest;
use App\Http\Requests\ProgressNote\UpdateProgressNoteRequest;
use App\Http\Resources\ProgressNoteResource;
use App\Models\ProgressNote;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressNoteController extends Controller
{
    public function index(Request $request, int $taskId): JsonResponse
    {
        $task = Task::findOrFail($taskId);
        $user = $request->user();

        if ($user->isMember() && $task->assignee_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
            ], 403);
        }

        $notes = $task->progressNotes()->with('user')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Progress notes retrieved successfully.',
            'data' => ProgressNoteResource::collection($notes),
            'meta' => null,
        ]);
    }

    public function store(StoreProgressNoteRequest $request, int $taskId): JsonResponse
    {
        $task = Task::findOrFail($taskId);
        $user = $request->user();

        if ($user->isMember() && $task->assignee_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
            ], 403);
        }

        $progressNote = ProgressNote::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'note' => $request->validated('note'),
            'status_snapshot' => $task->status,
        ]);

        $progressNote->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Progress note created successfully.',
            'data' => new ProgressNoteResource($progressNote),
            'meta' => null,
        ], 201);
    }

    public function update(UpdateProgressNoteRequest $request, int $id): JsonResponse
    {
        $progressNote = ProgressNote::with('task')->findOrFail($id);
        $user = $request->user();

        if ($user->isMember() && (
            $progressNote->user_id !== $user->id
            && $progressNote->task->assignee_id !== $user->id
        )) {
            return response()->json([
                'success' => false,
                'message' => 'You can only update your own progress notes.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
            ], 403);
        }

        $progressNote->update(['note' => $request->string('note')->toString()]);
        $progressNote->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Progress note updated successfully.',
            'data' => new ProgressNoteResource($progressNote),
            'meta' => null,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $progressNote = ProgressNote::with('task')->findOrFail($id);
        $user = $request->user();

        if ($user->isMember() && (
            $progressNote->user_id !== $user->id
            && $progressNote->task->assignee_id !== $user->id
        )) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own progress notes.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
            ], 403);
        }

        $progressNote->delete();

        return response()->json([
            'success' => true,
            'message' => 'Progress note deleted successfully.',
            'data' => null,
            'meta' => null,
        ]);
    }
}
