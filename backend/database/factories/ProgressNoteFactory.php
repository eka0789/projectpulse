<?php

namespace Database\Factories;

use App\Models\ProgressNote;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProgressNote> */
class ProgressNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'note' => fake()->paragraph(),
            'status_snapshot' => 'in_progress',
        ];
    }
}
