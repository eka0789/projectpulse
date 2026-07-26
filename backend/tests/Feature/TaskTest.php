<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_cannot_view_other_members_tasks(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = Client::create(['name' => 'Client A', 'company' => 'Comp A', 'created_by' => $admin->id]);
        $project = Project::create(['client_id' => $client->id, 'name' => 'Project A', 'deadline' => '2026-12-31', 'status' => 'active', 'created_by' => $admin->id]);

        $member1 = User::factory()->create(['role' => 'member']);
        $member2 = User::factory()->create(['role' => 'member']);

        $taskMember1 = Task::create([
            'project_id' => $project->id,
            'title' => 'Task for Member 1',
            'category' => 'backend',
            'assignee_id' => $member1->id,
            'priority' => 'high',
            'status' => 'todo',
            'created_by' => $admin->id,
        ]);

        $taskMember2 = Task::create([
            'project_id' => $project->id,
            'title' => 'Task for Member 2',
            'category' => 'frontend',
            'assignee_id' => $member2->id,
            'priority' => 'medium',
            'status' => 'todo',
            'created_by' => $admin->id,
        ]);

        // Member 1 accesses detail of Task for Member 2 -> 403 Forbidden
        $response = $this->actingAs($member1, 'sanctum')->getJson("/api/tasks/{$taskMember2->id}");
        $response->assertStatus(403);

        // Member 1 lists tasks -> only sees taskMember1
        $responseList = $this->actingAs($member1, 'sanctum')->getJson('/api/tasks');
        $responseList->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $taskMember1->id);
    }

    public function test_valid_task_status_transition_succeeds(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = Client::create(['name' => 'Client A', 'company' => 'Comp A', 'created_by' => $admin->id]);
        $project = Project::create(['client_id' => $client->id, 'name' => 'Project A', 'deadline' => '2026-12-31', 'status' => 'active', 'created_by' => $admin->id]);
        $member = User::factory()->create(['role' => 'member']);

        $task = Task::create([
            'project_id' => $project->id,
            'title' => 'Feature Task',
            'category' => 'backend',
            'assignee_id' => $member->id,
            'priority' => 'high',
            'status' => 'todo',
            'created_by' => $admin->id,
        ]);

        // Valid transition: todo -> in_progress
        $res1 = $this->actingAs($member, 'sanctum')->patchJson("/api/tasks/{$task->id}/status", ['status' => 'in_progress']);
        $res1->assertStatus(200)->assertJsonPath('data.status', 'in_progress');

        // Invalid transition: in_progress -> done (must go to review first)
        $res2 = $this->actingAs($member, 'sanctum')->patchJson("/api/tasks/{$task->id}/status", ['status' => 'done']);
        $res2->assertStatus(422)->assertJsonPath('error.code', 'INVALID_STATUS_TRANSITION');
    }
}
