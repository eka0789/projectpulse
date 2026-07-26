<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
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
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
            ], 403);
        }

        $comments = $task->comments()->with('user')->orderBy('created_at', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Comments retrieved successfully.',
            'data' => CommentResource::collection($comments),
            'meta' => null,
        ]);
    }

    public function store(StoreCommentRequest $request, int $taskId): JsonResponse
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
            'data' => new CommentResource($comment),
            'meta' => null,
        ], 201);
    }

    public function update(UpdateCommentRequest $request, int $id): JsonResponse
    {
        $comment = TaskComment::with('task')->findOrFail($id);
        $user = $request->user();

        if ($comment->user_id !== $user->id || (
            $user->isMember() && $comment->task->assignee_id !== $user->id
        )) {
            return response()->json([
                'success' => false,
                'message' => 'You can only edit your own comments.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
            ], 403);
        }

        $comment->update([
            'body' => $request->string('body')->toString(),
            'edited_at' => now(),
        ]);
        $comment->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Comment updated successfully.',
            'data' => new CommentResource($comment),
            'meta' => null,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $comment = TaskComment::with('task')->findOrFail($id);
        $user = $request->user();

        if ($comment->user_id !== $user->id || (
            $user->isMember() && $comment->task->assignee_id !== $user->id
        )) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own comments.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully.',
            'data' => null,
            'meta' => null,
        ]);
    }
}
