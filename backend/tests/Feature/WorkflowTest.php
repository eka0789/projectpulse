<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Notification;
use App\Models\ProgressNote;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $member;

    private User $otherMember;

    private Client $client;

    private Project $project;

    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->member = User::factory()->create(['role' => 'member']);
        $this->otherMember = User::factory()->create(['role' => 'member']);
        $this->client = Client::create([
            'name' => 'Jane Client',
            'company' => 'Acme',
            'created_by' => $this->admin->id,
        ]);
        $this->project = Project::create([
            'client_id' => $this->client->id,
            'name' => 'Project One',
            'deadline' => now()->addMonth()->toDateString(),
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);
        $this->task = Task::create([
            'project_id' => $this->project->id,
            'title' => 'Build secure API',
            'category' => 'backend',
            'assignee_id' => $this->member->id,
            'priority' => 'high',
            'status' => 'todo',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_complete_the_client_project_and_task_creation_flow(): void
    {
        $clientResponse = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/clients', [
                'name' => 'Second Client',
                'company' => 'Beta Ltd',
                'email' => 'client@beta.test',
            ])
            ->assertCreated()
            ->assertJsonPath('data.company', 'Beta Ltd');

        $clientId = $clientResponse->json('data.id');

        $projectResponse = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/projects', [
                'client_id' => $clientId,
                'name' => 'Beta Platform',
                'start_date' => now()->toDateString(),
                'deadline' => now()->addMonth()->toDateString(),
                'status' => 'active',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Beta Platform');

        $projectId = $projectResponse->json('data.id');

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/projects/{$projectId}/tasks", [
                'title' => 'Implement API',
                'category' => 'backend',
                'assignee_id' => $this->member->id,
                'priority' => 'high',
                'status' => 'todo',
                'estimated_hours' => 8,
            ])
            ->assertCreated()
            ->assertJsonPath('data.project_id', $projectId)
            ->assertJsonPath('data.assignee_id', $this->member->id);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->member->id,
            'type' => 'TaskAssigned',
        ]);
    }

    public function test_client_with_any_project_cannot_be_deleted(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/clients/{$this->client->id}")
            ->assertConflict()
            ->assertJsonPath('error.code', 'RESOURCE_CONFLICT');

        $this->assertNotSoftDeleted($this->client);
    }

    public function test_deleting_project_soft_deletes_its_tasks_atomically(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/projects/{$this->project->id}")
            ->assertOk();

        $this->assertSoftDeleted('projects', ['id' => $this->project->id]);
        $this->assertSoftDeleted('tasks', ['id' => $this->task->id]);
    }

    public function test_dashboard_returns_aggregated_workload_data(): void
    {
        TimeLog::create([
            'task_id' => $this->task->id,
            'user_id' => $this->member->id,
            'work_date' => now()->toDateString(),
            'duration_minutes' => 90,
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/dashboard/summary')
            ->assertOk()
            ->assertJsonPath('data.active_projects', 1)
            ->assertJsonPath('data.task_status_distribution.todo', 1)
            ->assertJsonPath('data.member_workloads.0.active_tasks', 1)
            ->assertJsonPath('data.member_workloads.0.logged_hours', 1.5)
            ->assertJsonPath('data.recent_projects.0.task_count', 1);
    }

    public function test_member_can_manage_own_time_logs_but_not_another_members_log(): void
    {
        $response = $this->actingAs($this->member, 'sanctum')
            ->postJson("/api/tasks/{$this->task->id}/time-logs", [
                'work_date' => now()->toDateString(),
                'duration_minutes' => 90,
                'note' => 'Implemented authorization tests.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $this->member->id);

        $timeLogId = $response->json('data.id');

        $this->actingAs($this->otherMember, 'sanctum')
            ->patchJson("/api/time-logs/{$timeLogId}", ['duration_minutes' => 60])
            ->assertForbidden();

        $this->actingAs($this->member, 'sanctum')
            ->patchJson("/api/time-logs/{$timeLogId}", ['duration_minutes' => 120])
            ->assertOk()
            ->assertJsonPath('data.duration_minutes', 120);
    }

    public function test_member_can_update_and_delete_only_own_progress_notes(): void
    {
        $response = $this->actingAs($this->member, 'sanctum')
            ->postJson("/api/tasks/{$this->task->id}/progress-notes", [
                'note' => 'Initial implementation completed.',
            ])
            ->assertCreated();

        $noteId = $response->json('data.id');

        $this->actingAs($this->otherMember, 'sanctum')
            ->patchJson("/api/progress-notes/{$noteId}", ['note' => 'Tampered note'])
            ->assertForbidden();

        $this->actingAs($this->member, 'sanctum')
            ->patchJson("/api/progress-notes/{$noteId}", ['note' => 'Tests are now complete.'])
            ->assertOk()
            ->assertJsonPath('data.note', 'Tests are now complete.');

        $this->actingAs($this->member, 'sanctum')
            ->deleteJson("/api/progress-notes/{$noteId}")
            ->assertOk();

        $this->assertDatabaseMissing('progress_notes', ['id' => $noteId]);
    }

    public function test_member_can_edit_and_soft_delete_only_own_comments(): void
    {
        $response = $this->actingAs($this->member, 'sanctum')
            ->postJson("/api/tasks/{$this->task->id}/comments", [
                'body' => 'Ready for review.',
            ])
            ->assertCreated();

        $commentId = $response->json('data.id');

        $this->actingAs($this->otherMember, 'sanctum')
            ->patchJson("/api/comments/{$commentId}", ['body' => 'Tampered'])
            ->assertForbidden();

        $this->actingAs($this->member, 'sanctum')
            ->patchJson("/api/comments/{$commentId}", ['body' => 'Ready for QA review.'])
            ->assertOk()
            ->assertJsonPath('data.body', 'Ready for QA review.');

        $this->actingAs($this->member, 'sanctum')
            ->deleteJson("/api/comments/{$commentId}")
            ->assertOk();

        $this->assertSoftDeleted('task_comments', ['id' => $commentId]);
    }

    public function test_notification_cannot_be_read_by_another_user(): void
    {
        $notification = Notification::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->member->id,
            'type' => 'TaskAssigned',
            'title' => 'Assigned',
            'message' => 'A task was assigned.',
        ]);

        $this->actingAs($this->otherMember, 'sanctum')
            ->patchJson("/api/notifications/{$notification->id}/read")
            ->assertNotFound()
            ->assertJsonPath('error.code', 'RESOURCE_NOT_FOUND');
    }

    public function test_member_cannot_write_to_an_unassigned_task(): void
    {
        $this->actingAs($this->otherMember, 'sanctum')
            ->postJson("/api/tasks/{$this->task->id}/time-logs", [
                'work_date' => now()->toDateString(),
                'duration_minutes' => 30,
            ])
            ->assertForbidden();

        $this->actingAs($this->otherMember, 'sanctum')
            ->postJson("/api/tasks/{$this->task->id}/progress-notes", [
                'note' => 'Unauthorized note.',
            ])
            ->assertForbidden();

        $this->actingAs($this->otherMember, 'sanctum')
            ->postJson("/api/tasks/{$this->task->id}/comments", [
                'body' => 'Unauthorized comment.',
            ])
            ->assertForbidden();

        $this->assertSame(0, TimeLog::count());
        $this->assertSame(0, ProgressNote::count());
        $this->assertSame(0, TaskComment::count());
    }
}
