<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProgressNoteController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TimeLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health Probes
Route::get('/health', [HealthController::class, 'liveness']);
Route::get('/health/ready', [HealthController::class, 'readiness']);

// Authentication
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);
});

// Protected API Routes
Route::middleware(['auth:sanctum', 'active', 'throttle:60,1'])->group(function () {
    // Auth & Profile
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::middleware('admin')->group(function () {
        Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
        Route::apiResource('members', MemberController::class);
        Route::apiResource('clients', ClientController::class);
        Route::apiResource('projects', ProjectController::class);

        Route::get('/projects/{project}/tasks', [TaskController::class, 'index']);
        Route::post('/projects/{project}/tasks', [TaskController::class, 'store']);
        Route::post('/projects/{project}/tasks/generate', [TaskController::class, 'generateAISuggestions']);
        Route::post('/projects/{project}/tasks/bulk', [TaskController::class, 'bulkStore']);

        Route::patch('/tasks/{id}', [TaskController::class, 'update']);
        Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);

        Route::get('/reports/time-logs', [ReportController::class, 'timeLogs']);
        Route::get('/reports/time-logs/export.csv', [ReportController::class, 'exportCsv']);
    });

    // Tasks are scoped by TaskController for member accounts.
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::get('/tasks/{id}', [TaskController::class, 'show']);
    Route::patch('/tasks/{id}/status', [TaskController::class, 'updateStatus']);

    // Time Logs
    Route::get('/tasks/{task}/time-logs', [TimeLogController::class, 'index']);
    Route::post('/tasks/{task}/time-logs', [TimeLogController::class, 'store']);
    Route::patch('/time-logs/{id}', [TimeLogController::class, 'update']);
    Route::delete('/time-logs/{id}', [TimeLogController::class, 'destroy']);

    // Progress Notes
    Route::get('/tasks/{task}/progress-notes', [ProgressNoteController::class, 'index']);
    Route::post('/tasks/{task}/progress-notes', [ProgressNoteController::class, 'store']);
    Route::patch('/progress-notes/{id}', [ProgressNoteController::class, 'update']);
    Route::delete('/progress-notes/{id}', [ProgressNoteController::class, 'destroy']);

    // Comments
    Route::get('/tasks/{task}/comments', [CommentController::class, 'index']);
    Route::post('/tasks/{task}/comments', [CommentController::class, 'store']);
    Route::patch('/comments/{id}', [CommentController::class, 'update']);
    Route::delete('/comments/{id}', [CommentController::class, 'destroy']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

});
