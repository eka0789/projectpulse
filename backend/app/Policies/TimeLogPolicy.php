<?php

namespace App\Policies;

use App\Models\TimeLog;
use App\Models\User;

class TimeLogPolicy
{
    public function view(User $user, TimeLog $timeLog): bool
    {
        return $user->isAdmin() || $timeLog->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isMember();
    }

    public function update(User $user, TimeLog $timeLog): bool
    {
        return $this->view($user, $timeLog);
    }

    public function delete(User $user, TimeLog $timeLog): bool
    {
        return $this->view($user, $timeLog);
    }
}
