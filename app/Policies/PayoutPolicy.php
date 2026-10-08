<?php

namespace App\Policies;

use App\Models\Payout;
use App\Models\PayoutSchedule;
use App\Models\User;

class PayoutPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOscaStaff();
    }

    public function view(User $user, Payout|PayoutSchedule $payout): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Payout $payout): bool
    {
        return $this->viewAny($user);
    }

    public function reverse(User $user, Payout $payout): bool
    {
        return $user->isAdmin();
    }
}
