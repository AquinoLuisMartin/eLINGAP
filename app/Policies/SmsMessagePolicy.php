<?php

namespace App\Policies;

use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use App\Models\User;

class SmsMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOscaStaff();
    }

    public function view(User $user, SmsMessage $message): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, SmsTemplate $template): bool
    {
        return $user->isAdmin();
    }
}
