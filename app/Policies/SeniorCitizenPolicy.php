<?php

namespace App\Policies;

use App\Enums\SeniorCitizenStatus;
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
        return $this->viewAny($user) && $seniorCitizen->status !== SeniorCitizenStatus::Deceased;
    }

    public function delete(User $user, SeniorCitizen $seniorCitizen): bool
    {
        return $user->isAdmin() && $seniorCitizen->status !== SeniorCitizenStatus::Deceased;
    }

    public function declareDeceased(User $user, SeniorCitizen $seniorCitizen): bool
    {
        return $this->viewAny($user);
    }

    public function correctDeath(User $user, SeniorCitizen $seniorCitizen): bool
    {
        return $user->isAdmin();
    }
}
