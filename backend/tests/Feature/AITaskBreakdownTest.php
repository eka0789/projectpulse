<?php

namespace Tests\Feature;

use App\Models\AITaskGeneration;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AITaskBreakdownTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $client = Client::create([
            'name' => 'AI Client',
            'company' => 'AI Company',
            'created_by' => $this->admin->id,
        ]);
        $this->project = Project::create([
            'client_id' => $client->id,
            'name' => 'AI Project',
            'deadline' => now()->addMonth()->toDateString(),
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_openai_demo_fallback_returns_reviewable_tasks_and_audit_id(): void
    {
        config()->set('services.ai.provider', 'openai');
        config()->set('services.ai.openai.api_key', null);
        config()->set('services.ai.demo_fallback_enabled', true);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/projects/{$this->project->id}/tasks/generate", [
                'brief' => 'Build a secure project delivery platform for an internal team.',
                'preferences' => ['maximum_tasks' => 2],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.provider', 'openai')
            ->assertJsonPath('data.source', 'demo_fallback')
            ->assertJsonCount(2, 'data.tasks');

        $this->assertIsInt($response->json('data.generation_id'));
        $this->assertDatabaseHas('ai_task_generations', [
            'id' => $response->json('data.generation_id'),
            'status' => 'success',
            'requested_by' => $this->admin->id,
        ]);
        $this->assertSame(0, Task::count(), 'Suggestions must not be persisted as tasks.');
    }

    public function test_gemini_demo_fallback_does_not_call_private_openai_methods(): void
    {
        config()->set('services.ai.provider', 'gemini');
        config()->set('services.ai.gemini.api_key', null);
        config()->set('services.ai.demo_fallback_enabled', true);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/projects/{$this->project->id}/tasks/generate", [
                'brief' => 'Create a mobile member experience with task tracking.',
            ])
            ->assertOk()
            ->assertJsonPath('data.provider', 'gemini')
            ->assertJsonPath('data.source', 'demo_fallback');
    }

    public function test_missing_provider_configuration_is_non_blocking_and_audited(): void
    {
        config()->set('services.ai.provider', 'openai');
        config()->set('services.ai.openai.api_key', null);
        config()->set('services.ai.demo_fallback_enabled', false);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/projects/{$this->project->id}/tasks/generate", [
                'brief' => 'Build a project dashboard with workload reporting.',
            ])
            ->assertServiceUnavailable()
            ->assertJsonPath('error.code', 'AI_PROVIDER_UNAVAILABLE');

        $this->assertDatabaseHas('ai_task_generations', [
            'project_id' => $this->project->id,
            'status' => 'failed',
            'error_code' => 'AI_PROVIDER_UNAVAILABLE',
        ]);
    }

    public function test_invalid_provider_json_is_rejected_and_audited(): void
    {
        config()->set('services.ai.provider', 'openai');
        config()->set('services.ai.openai.api_key', 'test-key');
        config()->set('services.ai.demo_fallback_enabled', false);
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => '{"unexpected":true}']]],
            ]),
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/projects/{$this->project->id}/tasks/generate", [
                'brief' => 'Build a project dashboard with workload reporting.',
            ])
            ->assertServiceUnavailable()
            ->assertJsonPath('error.code', 'AI_INVALID_RESPONSE');

        $this->assertSame('failed', AITaskGeneration::firstOrFail()->status);
    }

    public function test_bulk_task_save_is_atomic_and_rejects_non_member_assignees(): void
    {
        $payload = [
            'tasks' => [
                [
                    'title' => 'Valid task',
                    'category' => 'backend',
                    'priority' => 'high',
                    'assignee_id' => $this->admin->id,
                ],
                [
                    'title' => 'Another task',
                    'category' => 'qa',
                    'priority' => 'medium',
                ],
            ],
        ];

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/projects/{$this->project->id}/tasks/bulk", $payload)
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');

        $this->assertSame(0, Task::count());
    }
}
