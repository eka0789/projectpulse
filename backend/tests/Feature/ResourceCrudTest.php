<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_read_update_and_delete_a_client(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $created = $this->actingAs($admin, 'sanctum')->postJson('/api/clients', [
            'name' => 'Ari Contact',
            'company' => 'Ari Labs',
            'email' => 'ari@example.test',
        ])->assertCreated();
        $id = $created->json('data.id');

        $this->getJson("/api/clients/{$id}")->assertOk();
        $this->patchJson("/api/clients/{$id}", ['company' => 'Ari Studio'])
            ->assertOk()
            ->assertJsonPath('data.company', 'Ari Studio');
        $this->deleteJson("/api/clients/{$id}")->assertOk();
        $this->assertSoftDeleted('clients', ['id' => $id]);
    }

    public function test_admin_can_create_read_update_and_delete_a_project(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = Client::factory()->create(['created_by' => $admin->id]);

        $created = $this->actingAs($admin, 'sanctum')->postJson('/api/projects', [
            'client_id' => $client->id,
            'name' => 'Delivery project',
            'deadline' => now()->addMonth()->toDateString(),
            'status' => 'draft',
        ])->assertCreated();
        $id = $created->json('data.id');

        $this->getJson("/api/projects/{$id}")->assertOk();
        $this->patchJson("/api/projects/{$id}", ['status' => 'active'])->assertOk();
        $this->deleteJson("/api/projects/{$id}")->assertOk();
        $this->assertSoftDeleted('projects', ['id' => $id]);
    }

    public function test_time_log_rejects_invalid_duration_and_future_date(): void
    {
        $member = User::factory()->create();
        $task = Task::factory()->create(['assignee_id' => $member->id]);

        $this->actingAs($member, 'sanctum')
            ->postJson("/api/tasks/{$task->id}/time-logs", [
                'work_date' => now()->addDay()->toDateString(),
                'duration_minutes' => 0,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['work_date', 'duration_minutes'], 'error.details');
    }
}
