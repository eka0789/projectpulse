<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
                'error' => ['code' => 'FORBIDDEN', 'details' => null]
            ], 403);
        }

        $notes = $task->progressNotes()->with('user')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Progress notes retrieved successfully.',
            'data' => $notes,
            'meta' => null,
        ]);
    }

    public function store(Request $request, int $taskId): JsonResponse
    {
        $task = Task::findOrFail($taskId);
        $user = $request->user();

        if ($user->isMember() && $task->assignee_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null]
            ], 403);
        }

        $request->validate([
            'note' => 'required|string|min:3',
        ]);

        $progressNote = ProgressNote::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'note' => $request->note,
            'status_snapshot' => $task->status,
        ]);

        $progressNote->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Progress note created successfully.',
            'data' => $progressNote,
            'meta' => null,
        ], 201);
    }
}
