<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Admin and Member can view projects (Member sees projects relevant to their tasks or full list)
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Member can view if assigned to any task in project
        return $project->tasks()->where('assignee_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }
}
