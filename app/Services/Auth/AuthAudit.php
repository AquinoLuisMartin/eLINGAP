<?php

namespace App\Services\Auth;

use App\Models\AuditLog;
use App\Models\User;

class AuthAudit
{
    public static function record(string $action, User $target, ?User $actor = null, array $metadata = []): void
    {
        AuditLog::create(['user_id' => $actor?->id, 'action' => $action, 'auditable_type' => User::class,
            'auditable_id' => $target->id, 'new_values' => $metadata,
            'ip_address' => app()->runningInConsole() ? null : request()->ip()]);
    }
}
