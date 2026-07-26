<?php

namespace App\Services\AI;

class TaskBreakdownResult
{
    public function __construct(
        public bool $success,
        public array $tasks = [],
        public string $provider = 'openai',
        public string $model = 'gpt-4o-mini',
        public ?int $generationId = null,
        public int $latencyMs = 0,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public string $source = 'ai'
    ) {}
}
