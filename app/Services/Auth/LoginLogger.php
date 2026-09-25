<?php

namespace App\Services\Auth;

use App\Enums\LoginEvent;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\Request;

class LoginLogger
{
    public function __construct(private Request $request) {}

    public function success(LoginEvent $event, User $user): void
    {
        $this->write($event, true, $user, $user->username);
    }

    public function failure(LoginEvent $event, string $username, string $reason): void
    {
        $this->write($event, false, null, $username, $reason);
    }

    private function write(LoginEvent $event, bool $success, ?User $user, ?string $username, ?string $reason = null): void
    {
        LoginLog::create([
            'user_id' => $user?->id,
            'username_attempted' => $username === null ? null : mb_substr($username, 0, 100),
            'event' => $event,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'success' => $success,
            'failure_reason' => $reason,
        ]);
    }
}
