<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommentController extends Controller
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

        $comments = $task->comments()->with('user')->orderBy('created_at', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Comments retrieved successfully.',
            'data' => $comments,
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
            'body' => 'required|string|min:1',
        ]);

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'body' => $request->body,
        ]);

        // Trigger notification if commenter is not assignee
        if ($task->assignee_id && $task->assignee_id !== $user->id) {
            Notification::create([
                'id' => (string) Str::uuid(),
                'user_id' => $task->assignee_id,
                'type' => 'TaskCommentAdded',
                'title' => 'New Comment on Task',
                'message' => "{$user->name} commented on '{$task->title}'",
                'data' => ['task_id' => $task->id, 'comment_id' => $comment->id],
            ]);
        }

        $comment->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully.',
            'data' => $comment,
            'meta' => null,
        ], 201);
    }
}
