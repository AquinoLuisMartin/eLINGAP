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
        return $user->is_active && $user->isOscaStaff();
    }

    public function verify(User $user, SeniorCitizen $seniorCitizen): bool
    {
        return $user->is_active && $user->isOscaStaff();
    }

    public function manageId(User $user, SeniorCitizen $seniorCitizen): bool
    {
        return $user->is_active && $user->isOscaStaff();
    }

    public function configureId(User $user): bool
    {
        return $user->is_active && $user->isOscaStaff();
    }

    public function update(User $user, SeniorCitizen $seniorCitizen): bool
    {
        return $this->viewAny($user) && ! in_array($seniorCitizen->status, [SeniorCitizenStatus::Deceased, SeniorCitizenStatus::Transferred], true);
    }

    public function delete(User $user, SeniorCitizen $seniorCitizen): bool
    {
        return $user->isAdmin() && ! in_array($seniorCitizen->status, [SeniorCitizenStatus::Deceased, SeniorCitizenStatus::Transferred], true);
    }

    public function declareDeceased(User $user, SeniorCitizen $seniorCitizen): bool
    {
        return $this->viewAny($user);
    }

    public function correctDeath(User $user, SeniorCitizen $seniorCitizen): bool
    {
        return $user->isAdmin();
    }

    public function transfer(User $user, SeniorCitizen $seniorCitizen): bool
    {
        return $user->is_active && $user->isOscaStaff()
            && $seniorCitizen->status === SeniorCitizenStatus::Verified;
    }
}
