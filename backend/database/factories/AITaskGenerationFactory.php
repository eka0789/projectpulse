<?php

namespace Database\Factories;

use App\Models\AITaskGeneration;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AITaskGeneration> */
class AITaskGenerationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'requested_by' => User::factory()->state(['role' => 'admin']),
            'provider' => 'demo',
            'model' => 'deterministic',
            'brief_hash' => hash('sha256', fake()->paragraph()),
            'request_payload' => [],
            'response_payload' => [],
            'status' => 'success',
            'latency_ms' => fake()->numberBetween(1, 100),
        ];
    }
}
