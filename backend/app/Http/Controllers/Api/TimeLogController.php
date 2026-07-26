<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TimeLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimeLogController extends Controller
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

        $timeLogs = $task->timeLogs()->with('user')->orderBy('work_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Time logs retrieved successfully.',
            'data' => $timeLogs,
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
                'message' => 'Members can only log time for tasks assigned to them.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null]
            ], 403);
        }

        $request->validate([
            'work_date' => 'required|date|before_or_equal:today',
            'duration_minutes' => 'required|integer|min:1|max:1440',
            'note' => 'nullable|string',
        ]);

        $timeLog = TimeLog::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'work_date' => $request->work_date,
            'duration_minutes' => $request->duration_minutes,
            'note' => $request->note,
        ]);

        $timeLog->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Time log created successfully.',
            'data' => $timeLog,
            'meta' => null,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $timeLog = TimeLog::findOrFail($id);
        $user = $request->user();

        if ($user->isMember() && $timeLog->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null]
            ], 403);
        }

        $request->validate([
            'work_date' => 'sometimes|required|date|before_or_equal:today',
            'duration_minutes' => 'sometimes|required|integer|min:1|max:1440',
            'note' => 'nullable|string',
        ]);

        $timeLog->update($request->only(['work_date', 'duration_minutes', 'note']));

        return response()->json([
            'success' => true,
            'message' => 'Time log updated successfully.',
            'data' => $timeLog,
            'meta' => null,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $timeLog = TimeLog::findOrFail($id);
        $user = $request->user();

        if ($user->isMember() && $timeLog->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null]
            ], 403);
        }

        $timeLog->delete();

        return response()->json([
            'success' => true,
            'message' => 'Time log deleted successfully.',
            'data' => null,
            'meta' => null,
        ]);
    }
}
