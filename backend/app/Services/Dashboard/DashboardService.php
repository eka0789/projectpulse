<?php

namespace App\Services\Dashboard;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getSummary(?User $user = null): array
    {
        $today = Carbon::today()->toDateString();
        $endOfWeek = Carbon::now()->endOfWeek()->toDateString();

        $activeProjects = Project::where('status', 'active')->count();
        $completedProjects = Project::where('status', 'completed')->count();

        $overdueTasksQuery = Task::where('status', '!=', 'done')
            ->whereNotNull('deadline')
            ->where('deadline', '<', $today);

        $tasksDueTodayQuery = Task::where('status', '!=', 'done')
            ->where('deadline', '=', $today);

        $tasksDueThisWeekQuery = Task::where('status', '!=', 'done')
            ->whereBetween('deadline', [$today, $endOfWeek]);

        if ($user && $user->isMember()) {
            $overdueTasksQuery->where('assignee_id', $user->id);
            $tasksDueTodayQuery->where('assignee_id', $user->id);
            $tasksDueThisWeekQuery->where('assignee_id', $user->id);
        }

        $taskDistribution = [
            'todo' => Task::where('status', 'todo')->count(),
            'in_progress' => Task::where('status', 'in_progress')->count(),
            'review' => Task::where('status', 'review')->count(),
            'done' => Task::where('status', 'done')->count(),
        ];

        // Member workloads
        $members = User::where('role', 'member')->where('is_active', true)->get();
        $memberWorkloads = $members->map(function ($member) use ($today) {
            $activeTasks = Task::where('assignee_id', $member->id)
                ->where('status', '!=', 'done')
                ->count();

            $estimatedHours = Task::where('assignee_id', $member->id)
                ->where('status', '!=', 'done')
                ->sum('estimated_hours');

            $loggedMinutes = DB::table('time_logs')
                ->join('tasks', 'time_logs.task_id', '=', 'tasks.id')
                ->where('tasks.assignee_id', $member->id)
                ->sum('duration_minutes');

            $overdueCount = Task::where('assignee_id', $member->id)
                ->where('status', '!=', 'done')
                ->whereNotNull('deadline')
                ->where('deadline', '<', $today)
                ->count();

            return [
                'user_id' => $member->id,
                'name' => $member->name,
                'avatar_url' => $member->avatar_url,
                'job_title' => $member->job_title,
                'active_tasks' => $activeTasks,
                'estimated_hours' => (float) $estimatedHours,
                'logged_hours' => round($loggedMinutes / 60, 1),
                'overdue_tasks' => $overdueCount,
            ];
        });

        $recentProjects = Project::with('client')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'client_name' => $project->client->name ?? 'N/A',
                    'company' => $project->client->company ?? 'N/A',
                    'status' => $project->status,
                    'deadline' => $project->deadline ? $project->deadline->format('Y-m-d') : null,
                    'task_count' => $project->tasks()->count(),
                ];
            });

        return [
            'active_projects' => $activeProjects,
            'completed_projects' => $completedProjects,
            'overdue_tasks' => $overdueTasksQuery->count(),
            'tasks_due_today' => $tasksDueTodayQuery->count(),
            'tasks_due_this_week' => $tasksDueThisWeekQuery->count(),
            'task_status_distribution' => $taskDistribution,
            'member_workloads' => $memberWorkloads,
            'recent_projects' => $recentProjects,
        ];
    }
}
