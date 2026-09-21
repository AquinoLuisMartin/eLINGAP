<?php

namespace App\Policies;

use App\Models\SeniorCitizen;
use App\Models\User;

class SeniorCitizenPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOscaStaff();
    }

    public function view(User $user, SeniorCitizen $seniorCitizen): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, SeniorCitizen $seniorCitizen): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, SeniorCitizen $seniorCitizen): bool
    {
        return $user->isAdmin();
    }
}
