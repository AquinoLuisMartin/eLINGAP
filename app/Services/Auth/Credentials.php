<?php

namespace App\Services\Auth;

use App\Enums\LoginEvent;
use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Rules\NewPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class Credentials
{
    public function __construct(private LoginLogger $logger) {}

    public function change(User $actor, #[\SensitiveParameter] string $current, #[\SensitiveParameter] string $password): void
    {
        $session = request()->hasSession() ? request()->session() : session();
        $user = DB::transaction(function () use ($actor, $current, $password, $session): ?User {
            $user = User::query()->lockForUpdate()->findOrFail($actor->id);
            abort_unless($user->is_active && $user->credential_version === $actor->credential_version, 403);
            if (! Hash::check($current, $user->password_hash)) {
                AuthAudit::record('user.password_change_failed', $user, $user, ['reason' => 'current_password']);

                return null;
            }
            $this->replaceLocked($user, $password, LoginEvent::PasswordChanged, $user, $session->getId());

            return $user;
        });
        if (! $user) {
            throw ValidationException::withMessages(['current_password' => 'The current password is incorrect.']);
        }
        Auth::setUser($user);
        $session->put('credential_version', $user->credential_version);
        $session->regenerate(true);
        $session->regenerateToken();
    }

    // The caller holds the target user lock inside a database transaction.
    public function replaceLocked(User $user, #[\SensitiveParameter] string $password, LoginEvent $event, ?User $actor = null, ?string $keepSession = null, bool $mustChange = false): void
    {
        validator(['password' => $password], ['password' => ['required', 'string', new NewPassword($user)]])->validate();
        $user->forceFill(['password_hash' => $password, 'must_change_password' => $mustChange])->save();
        $this->revokeLocked($user, $actor, $keepSession);
        $this->logger->success($event, $user);
        AuthAudit::record($event === LoginEvent::PasswordReset ? 'user.password_reset' : 'user.password_changed', $user, $actor);
    }

    // The caller holds the target user lock inside a database transaction.
    public function revokeLocked(User $user, ?User $actor = null, ?string $keepSession = null): void
    {
        $user->forceFill(['credential_version' => $user->credential_version + 1, 'remember_token' => null])->save();
        foreach (PasswordResetRequest::query()->where('user_id', $user->id)->whereIn('status', ['PENDING', 'APPROVED'])->lockForUpdate()->get() as $reset) {
            $reset->forceFill(['status' => 'EXPIRED', 'recovery_code_hash' => null])->save();
            AuthAudit::record('user.recovery_expired', $user, $actor, ['request_id' => $reset->id, 'reason' => 'credentials_revoked']);
        }
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)->when($keepSession, fn ($query) => $query->where('id', '!=', $keepSession))->delete();
        }
    }
}
