<?php

namespace App\Enums;

enum TaskStatus: string
{
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case REVIEW = 'review';
    case DONE = 'done';

    public function isValidTransitionTo(TaskStatus $targetStatus): bool
    {
        return match ($this) {
            self::TODO => $targetStatus === self::IN_PROGRESS,
            self::IN_PROGRESS => $targetStatus === self::REVIEW,
            self::REVIEW => in_array($targetStatus, [self::DONE, self::IN_PROGRESS]),
            self::DONE => false,
        };
    }
}
