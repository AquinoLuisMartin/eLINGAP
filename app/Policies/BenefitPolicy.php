<?php

namespace App\Policies;

use App\Models\User;

class BenefitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && ($user->isAdmin() || $user->isOscaStaff());
    }

    public function manage(User $user): bool
    {
        return $user->is_active && $user->isOscaStaff();
    }

    public function reverse(User $user): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
