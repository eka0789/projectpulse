<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@projectpulse.test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@projectpulse.test',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'token_type',
                    'user' => ['id', 'name', 'email', 'role']
                ]
            ]);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'test@projectpulse.test',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@projectpulse.test',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'inactive@projectpulse.test',
            'password' => bcrypt('password123'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'inactive@projectpulse.test',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }
}
