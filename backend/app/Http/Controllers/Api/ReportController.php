<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimeLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function timeLogs(Request $request): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
            ], 403);
        }

        $query = TimeLog::with(['task.project.client', 'user']);

        if ($request->filled('project_id')) {
            $query->whereHas('task', function ($q) use ($request) {
                $q->where('project_id', $request->project_id);
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->where('work_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('work_date', '<=', $request->date_to);
        }

        $timeLogs = $query->orderBy('work_date', 'desc')->get();

        $totalMinutes = $timeLogs->sum('duration_minutes');

        return response()->json([
            'success' => true,
            'message' => 'Time log report generated.',
            'data' => [
                'total_hours' => round($totalMinutes / 60, 2),
                'total_entries' => $timeLogs->count(),
                'time_logs' => $timeLogs,
            ],
            'meta' => null,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse|JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
                'error' => ['code' => 'FORBIDDEN', 'details' => null],
            ], 403);
        }

        $query = TimeLog::with(['task.project.client', 'user']);

        if ($request->filled('project_id')) {
            $query->whereHas('task', function ($q) use ($request) {
                $q->where('project_id', $request->project_id);
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $timeLogs = $query->orderBy('work_date', 'desc')->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=time_logs_report.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($timeLogs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Date', 'User', 'Task Title', 'Project', 'Client', 'Duration (Hours)', 'Note']);

            foreach ($timeLogs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->work_date->format('Y-m-d'),
                    $log->user->name ?? 'N/A',
                    $log->task->title ?? 'N/A',
                    $log->task->project->name ?? 'N/A',
                    $log->task->project->client->company ?? 'N/A',
                    round($log->duration_minutes / 60, 2),
                    $log->note,
                ]);
            }

            fclose($file);
        }, 200, $headers);
    }
}
