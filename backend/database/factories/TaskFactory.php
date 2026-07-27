<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Task> */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement(['frontend', 'backend', 'design', 'qa', 'devops', 'management', 'other']),
            'assignee_id' => User::factory(),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => 'todo',
            'estimated_hours' => fake()->randomFloat(1, 1, 40),
            'start_date' => now()->toDateString(),
            'deadline' => now()->addWeek()->toDateString(),
            'created_by' => User::factory()->state(['role' => 'admin']),
            'source' => 'manual',
        ];
    }
}
