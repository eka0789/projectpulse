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
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// Protected API Routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth & Profile
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Dashboard
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

    // Members (Admin Only)
    Route::apiResource('members', MemberController::class);

    // Clients (Admin Only)
    Route::apiResource('clients', ClientController::class);

    // Projects
    Route::apiResource('projects', ProjectController::class);

    // AI Task Breakdown & Bulk Task Insert
    Route::post('/projects/{project}/tasks/generate', [TaskController::class, 'generateAISuggestions']);
    Route::post('/projects/{project}/tasks/bulk', [TaskController::class, 'bulkStore']);

    // Tasks & Nested Tasks
    Route::get('/projects/{project}/tasks', [TaskController::class, 'index']);
    Route::post('/projects/{project}/tasks', [TaskController::class, 'store']);
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::get('/tasks/{id}', [TaskController::class, 'show']);
    Route::patch('/tasks/{id}', [TaskController::class, 'update']);
    Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);
    Route::patch('/tasks/{id}/status', [TaskController::class, 'updateStatus']);

    // Time Logs
    Route::get('/tasks/{task}/time-logs', [TimeLogController::class, 'index']);
    Route::post('/tasks/{task}/time-logs', [TimeLogController::class, 'store']);
    Route::patch('/time-logs/{id}', [TimeLogController::class, 'update']);
    Route::delete('/time-logs/{id}', [TimeLogController::class, 'destroy']);

    // Progress Notes
    Route::get('/tasks/{task}/progress-notes', [ProgressNoteController::class, 'index']);
    Route::post('/tasks/{task}/progress-notes', [ProgressNoteController::class, 'store']);

    // Comments
    Route::get('/tasks/{task}/comments', [CommentController::class, 'index']);
    Route::post('/tasks/{task}/comments', [CommentController::class, 'store']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // Reports
    Route::get('/reports/time-logs', [ReportController::class, 'timeLogs']);
    Route::get('/reports/time-logs/export.csv', [ReportController::class, 'exportCsv']);
});
