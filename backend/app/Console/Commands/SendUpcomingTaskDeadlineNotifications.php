<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SendUpcomingTaskDeadlineNotifications extends Command
{
    protected $signature = 'notifications:send-upcoming-deadlines';

    protected $description = 'Send idempotent notification to assigned members for tasks due tomorrow (H-1)';

    public function handle(): int
    {
        $tomorrow = Carbon::tomorrow()->toDateString();

        $tasksDueTomorrow = Task::where('status', '!=', 'done')
            ->whereNotNull('assignee_id')
            ->whereNotNull('deadline')
            ->whereDate('deadline', $tomorrow)
            ->get();

        $count = 0;
        foreach ($tasksDueTomorrow as $task) {
            // Check idempotency: avoid sending duplicate deadline notifications for the same task and deadline date
            $alreadyNotified = Notification::where('user_id', $task->assignee_id)
                ->where('type', 'UpcomingTaskDeadlineH1')
                ->whereJsonContains('data->task_id', $task->id)
                ->whereJsonContains('data->deadline', $tomorrow)
                ->exists();

            if (! $alreadyNotified) {
                Notification::create([
                    'id' => (string) Str::uuid(),
                    'user_id' => $task->assignee_id,
                    'type' => 'UpcomingTaskDeadlineH1',
                    'title' => 'Task Deadline Tomorrow (H-1)',
                    'message' => "Reminder: Task '{$task->title}' is due tomorrow ({$tomorrow}).",
                    'data' => [
                        'task_id' => $task->id,
                        'project_id' => $task->project_id,
                        'deadline' => $tomorrow,
                    ],
                ]);
                $count++;
            }
        }

        $this->info("Sent {$count} upcoming deadline notifications.");

        return Command::SUCCESS;
    }
}
