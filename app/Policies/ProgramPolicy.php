<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;

class ProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOscaStaff();
    }

    public function view(User $user, Program $program): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Program $program): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Program $program): bool
    {
        return $user->isAdmin();
    }
}
