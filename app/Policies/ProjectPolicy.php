<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Services\PlanAccess;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $project->user_id === $user->id || $project->members()->where('users.id', $user->id)->exists();
    }
    public function update(User $user, Project $project): bool
    {
        return $this->manageMembers($user, $project);
    }
    public function manageMembers(User $user, Project $project): bool
    {
        if ($project->user_id === $user->id) return true;
        return app(PlanAccess::class)->allows($project->owner, 'roles') &&
            $project->members()->where('users.id', $user->id)->wherePivot('role', 'manager')->exists();
    }
    public function editTasks(User $user, Project $project): bool
    {
        if ($project->user_id === $user->id) return true;
        $member = $project->members()->where('users.id', $user->id)->first();
        // A viewer never gains write access when the project loses a paid tier.
        return $member && in_array($member->pivot->role, ['member', 'manager'], true);
    }
    public function delete(User $user, Project $project): bool { return $this->manageMembers($user, $project); }
}
