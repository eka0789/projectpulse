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
}
