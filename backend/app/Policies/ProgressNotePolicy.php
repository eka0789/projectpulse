<?php

namespace App\Policies;

use App\Models\ProgressNote;
use App\Models\User;

class ProgressNotePolicy
{
    public function view(User $user, ProgressNote $note): bool
    {
        return $user->isAdmin() || $note->task?->assignee_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ProgressNote $note): bool
    {
        return $user->isAdmin()
            || ($note->user_id === $user->id && $note->task?->assignee_id === $user->id);
    }

    public function delete(User $user, ProgressNote $note): bool
    {
        return $this->update($user, $note);
    }
}
