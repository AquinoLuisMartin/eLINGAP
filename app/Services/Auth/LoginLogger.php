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
        $this->write($event, true, $user, $user->employee_id);
    }

    public function failure(LoginEvent $event, string $employeeId, string $reason): void
    {
        $this->write($event, false, null, $employeeId, $reason);
    }

    private function write(LoginEvent $event, bool $success, ?User $user, ?string $employeeId, ?string $reason = null): void
    {
        LoginLog::create([
            'user_id' => $user?->id,
            'employee_id_attempted' => $employeeId === null ? null : mb_substr($employeeId, 0, 100),
            'event' => $event,
            'ip_address' => $this->request->ip(),
            'user_agent' => mb_substr($this->request->userAgent() ?? '', 0, 1000),
            'success' => $success,
            'failure_reason' => $reason,
        ]);
    }
}
