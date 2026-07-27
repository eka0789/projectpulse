<?php

namespace App\Services\Dashboard;

use App\Models\Project;
use App\Models\Task;
use App\Models\TimeLog;
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
            ->where('deadline', '!=', $today)
            ->whereBetween('deadline', [$today, $endOfWeek]);

        if ($user && $user->isMember()) {
            $overdueTasksQuery->where('assignee_id', $user->id);
            $tasksDueTodayQuery->where('assignee_id', $user->id);
            $tasksDueThisWeekQuery->where('assignee_id', $user->id);
        }

        $taskCounts = Task::query()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->when($user && $user->isMember(), fn ($q) => $q->where('assignee_id', $user->id))
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $taskDistribution = collect(['todo', 'in_progress', 'review', 'done'])
            ->mapWithKeys(fn (string $status): array => [
                $status => (int) ($taskCounts[$status] ?? 0),
            ])
            ->all();

        $loggedMinutesByUser = TimeLog::query()
            ->select('user_id', DB::raw('SUM(duration_minutes) as aggregate'))
            ->groupBy('user_id')
            ->pluck('aggregate', 'user_id');
        $members = User::query()
            ->where('role', 'member')
            ->where('is_active', true)
            ->withCount([
                'assignedTasks as active_tasks' => fn ($query) => $query->where('status', '!=', 'done'),
                'assignedTasks as overdue_tasks' => fn ($query) => $query
                    ->where('status', '!=', 'done')
                    ->whereNotNull('deadline')
                    ->where('deadline', '<', $today),
            ])
            ->withSum(
                ['assignedTasks as estimated_hours' => fn ($query) => $query->where('status', '!=', 'done')],
                'estimated_hours'
            )
            ->get();
        $memberWorkloads = $members->map(function ($member) use ($loggedMinutesByUser) {
            return [
                'user_id' => $member->id,
                'name' => $member->name,
                'avatar_url' => $member->avatar_url,
                'job_title' => $member->job_title,
                'active_tasks' => $member->active_tasks,
                'estimated_hours' => (float) ($member->estimated_hours ?? 0),
                'logged_hours' => round(((int) ($loggedMinutesByUser[$member->id] ?? 0)) / 60, 1),
                'overdue_tasks' => $member->overdue_tasks,
            ];
        });

        $recentProjects = Project::with('client')
            ->withCount('tasks')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'client_name' => $project->client?->name ?? 'N/A',
                    'company' => $project->client?->company ?? 'N/A',
                    'status' => $project->status,
                    'deadline' => $project->deadline ? $project->deadline->format('Y-m-d') : null,
                    'task_count' => $project->tasks_count,
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
