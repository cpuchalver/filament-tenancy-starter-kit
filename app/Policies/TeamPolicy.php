<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function view(User $user, Team $team): bool
    {
        return $team->users()->whereKey($user)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Team $team): bool
    {
        return $team->isOwnedBy($user);
    }

    public function manageMembers(User $user, Team $team): bool
    {
        return $team->isOwnedBy($user);
    }

    public function delete(User $user, Team $team): bool
    {
        return $team->isOwnedBy($user);
    }
}
