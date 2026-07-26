<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeadlineNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_deadline_command_is_idempotent_for_a_task_and_deadline(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);
        $client = Client::create([
            'name' => 'Client',
            'company' => 'Company',
            'created_by' => $admin->id,
        ]);
        $project = Project::create([
            'client_id' => $client->id,
            'name' => 'Project',
            'deadline' => now()->addWeek()->toDateString(),
            'status' => 'active',
            'created_by' => $admin->id,
        ]);
        Task::create([
            'project_id' => $project->id,
            'title' => 'Due tomorrow',
            'category' => 'qa',
            'assignee_id' => $member->id,
            'priority' => 'high',
            'status' => 'in_progress',
            'deadline' => Carbon::tomorrow()->toDateString(),
            'created_by' => $admin->id,
        ]);

        $this->assertSame(
            1,
            Task::whereDate('deadline', Carbon::tomorrow()->toDateString())->count()
        );

        $this->artisan('notifications:send-upcoming-deadlines')
            ->expectsOutput('Sent 1 upcoming deadline notifications.')
            ->assertSuccessful();
        $this->artisan('notifications:send-upcoming-deadlines')->assertSuccessful();

        $this->assertSame(
            1,
            Notification::where('user_id', $member->id)
                ->where('type', 'UpcomingTaskDeadlineH1')
                ->count()
        );
    }
}
