<?php

namespace App\Services\AI;

class TaskBreakdownRequestData
{
    public function __construct(
        public string $brief,
        public array $preferences = [],
        public ?int $projectId = null,
        public ?int $userId = null
    ) {}

    public function maximumTasks(): int
    {
        return max(1, min((int) ($this->preferences['maximum_tasks'] ?? 15), 25));
    }
}
