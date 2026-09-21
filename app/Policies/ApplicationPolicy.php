<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOscaStaff();
    }

    public function view(User $user, Application $application): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isOscaStaff();
    }

    public function review(User $user, Application $application): bool
    {
        return $user->isAdmin() || $user->isOscaStaff();
    }
}
