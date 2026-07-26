<?php

namespace Tests\Unit;

use App\Enums\TaskStatus;
use PHPUnit\Framework\TestCase;

class TaskStateServiceTest extends TestCase
{
    public function test_todo_can_only_transition_to_in_progress(): void
    {
        $status = TaskStatus::TODO;

        $this->assertTrue($status->isValidTransitionTo(TaskStatus::IN_PROGRESS));
        $this->assertFalse($status->isValidTransitionTo(TaskStatus::REVIEW));
        $this->assertFalse($status->isValidTransitionTo(TaskStatus::DONE));
    }

    public function test_review_can_transition_to_done_or_in_progress(): void
    {
        $status = TaskStatus::REVIEW;

        $this->assertTrue($status->isValidTransitionTo(TaskStatus::DONE));
        $this->assertTrue($status->isValidTransitionTo(TaskStatus::IN_PROGRESS));
        $this->assertFalse($status->isValidTransitionTo(TaskStatus::TODO));
    }
}
