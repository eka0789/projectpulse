<?php

namespace App\Services\AI;

class TaskBreakdownItemData
{
    public function __construct(
        public string $temporaryId,
        public string $title,
        public ?string $description,
        public string $category,
        public float $estimatedHours,
        public string $priority,
        public array $acceptanceCriteria = []
    ) {}

    public function toArray(): array
    {
        return [
            'temporary_id' => $this->temporaryId,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'estimated_hours' => $this->estimatedHours,
            'priority' => $this->priority,
            'acceptance_criteria' => $this->acceptanceCriteria,
        ];
    }
}
