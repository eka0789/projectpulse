<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_disabled_by_default(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Unexpected Admin',
            'email' => 'attacker@example.test',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'FORBIDDEN');

        $this->assertDatabaseMissing('users', ['email' => 'attacker@example.test']);
    }

    public function test_development_registration_can_only_create_members(): void
    {
        config()->set('projectpulse.allow_public_registration', true);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'New User',
            'email' => 'new-user@example.test',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.role', 'member');
        $this->assertDatabaseHas('users', [
            'email' => 'new-user@example.test',
            'role' => 'member',
        ]);
    }

    public function test_member_cannot_access_admin_endpoints(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        foreach ([
            '/api/dashboard/summary',
            '/api/members',
            '/api/clients',
            '/api/projects',
            '/api/reports/time-logs',
        ] as $endpoint) {
            $this->actingAs($member, 'sanctum')
                ->getJson($endpoint)
                ->assertForbidden()
                ->assertJsonPath('error.code', 'FORBIDDEN');
        }
    }

    public function test_inactive_authenticated_user_is_rejected(): void
    {
        $user = User::factory()->create([
            'role' => 'member',
            'is_active' => false,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/me')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_validation_errors_use_the_api_error_contract(): void
    {
        $this->postJson('/api/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'error' => [
                    'details' => ['email', 'password'],
                ],
            ]);
    }

    public function test_unauthenticated_errors_use_the_api_error_contract(): void
    {
        $this->getJson('/api/tasks')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_api_responses_include_a_request_id(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertHeader('X-Request-ID');
    }
}
