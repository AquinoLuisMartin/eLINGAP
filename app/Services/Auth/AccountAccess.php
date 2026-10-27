<?php

namespace App\Services\Auth;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AccountAccess
{
    public function __construct(private Credentials $credentials) {}

    // The caller starts a transaction before locking the administrator and target.
    public function lockTarget(User $actor, User $target, string $ability): User
    {
        $users = User::query()->whereIn('id', [$actor->id, $target->id])->orderBy('id')->lockForUpdate()->with('role')->get()->keyBy('id');
        $admin = $users[$actor->id];
        $user = $users[$target->id];
        Gate::forUser($admin)->authorize($ability, $user);
        abort_unless($admin->credential_version === $actor->credential_version, 403);

        return $user;
    }

    public function setStatus(User $actor, User $target, ?bool $active): User
    {
        return DB::transaction(function () use ($actor, $target, $active): User {
            $user = $this->lockTarget($actor, $target, 'manageAccess');
            $before = $user->is_active;
            $user->update(['is_active' => $active ?? ! $before]);
            if ($before !== $user->is_active) {
                $this->credentials->revokeLocked($user, $actor);
                AuditLog::create(['user_id' => $actor->id, 'action' => 'user.access_changed', 'auditable_type' => User::class,
                    'auditable_id' => $user->id, 'old_values' => ['is_active' => $before], 'new_values' => ['is_active' => $user->is_active],
                    'ip_address' => app()->runningInConsole() ? null : request()->ip()]);
            }

            return $user;
        });
    }
}
