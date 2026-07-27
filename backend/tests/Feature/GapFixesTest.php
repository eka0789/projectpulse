<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GapFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_time_logs_as_pdf(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create();
        $task = Task::factory()->create(['assignee_id' => $member->id]);
        TimeLog::factory()->create([
            'task_id' => $task->id,
            'user_id' => $member->id,
            'duration_minutes' => 90,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->get('/api/reports/time-logs/export.pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertStringStartsWith('%PDF-1.4', $response->getContent());
    }

    public function test_task_update_rejects_a_stale_timestamp(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $task = Task::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/tasks/{$task->id}", [
                'title' => 'Stale change',
                'updated_at' => $task->updated_at->subSecond()->toISOString(),
            ])
            ->assertConflict()
            ->assertJsonPath('error.code', 'STALE_RESOURCE');

        $this->assertNotSame('Stale change', $task->fresh()->title);
    }

    public function test_resource_factories_build_a_complete_graph(): void
    {
        $project = Project::factory()->create();
        $client = Client::findOrFail($project->client_id);

        $this->assertNotNull($client->creator);
        $this->assertNotNull($project->creator);
    }
}
