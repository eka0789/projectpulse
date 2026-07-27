<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TimeLog\StoreTimeLogRequest;
use App\Http\Requests\TimeLog\UpdateTimeLogRequest;
use App\Http\Resources\TimeLogResource;
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
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
            ], 403);
        }

        $timeLogs = $task->timeLogs()->with('user')->orderBy('work_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Time logs retrieved successfully.',
            'data' => TimeLogResource::collection($timeLogs),
            'meta' => null,
        ]);
    }

    public function store(StoreTimeLogRequest $request, int $taskId): JsonResponse
    {
        $task = Task::findOrFail($taskId);
        $user = $request->user();

        if ($user->isMember() && $task->assignee_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Time can only be logged by the assigned member.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
            ], 403);
        }

        $timeLog = TimeLog::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            ...$request->validated(),
        ]);

        $timeLog->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Time log created successfully.',
            'data' => new TimeLogResource($timeLog),
            'meta' => null,
        ], 201);
    }

    public function update(UpdateTimeLogRequest $request, int $id): JsonResponse
    {
        $timeLog = TimeLog::findOrFail($id);
        $user = $request->user();

        if ($user->isMember() && $timeLog->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
            ], 403);
        }

        $timeLog->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Time log updated successfully.',
            'data' => new TimeLogResource($timeLog),
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
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
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
