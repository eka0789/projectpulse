<?php

namespace App\Policies;

use App\Models\TaskComment;
use App\Models\User;

class TaskCommentPolicy
{
    public function view(User $user, TaskComment $comment): bool
    {
        return $user->isAdmin() || $comment->task?->assignee_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, TaskComment $comment): bool
    {
        return $user->isAdmin() || $comment->user_id === $user->id;
    }

    public function delete(User $user, TaskComment $comment): bool
    {
        return $this->update($user, $comment);
    }
}
