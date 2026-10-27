<?php

namespace App\Services\Auth;

use App\Enums\LoginEvent;
use App\Enums\PasswordResetStatus;
use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PasswordRecovery
{
    public const CONFIRMATION = 'If the account is eligible, your password reset request has been recorded. Please contact the OSCA Administrator for identity verification and further assistance.';

    public const INVALID_CODE = 'The recovery details are invalid or expired. Contact your administrator for assistance.';

    public function __construct(private Credentials $credentials) {}

    public function request(string $username, ?User $actor = null): ?PasswordResetRequest
    {
        return DB::transaction(function () use ($username, $actor): ?PasswordResetRequest {
            $id = User::query()->whereRaw('lower(username) = ?', [Username::normalize($username)])->value('id');
            if (! $id) {
                return null;
            }
            $users = $this->lockUsers([$id, ...($actor ? [$actor->id] : [])]);
            $user = $users[$id];
            if ($actor) {
                $this->authorize($users[$actor->id], $user, $actor);
            }
            if (! $this->eligible($user)) {
                return null;
            }
            $existing = PasswordResetRequest::query()->where('user_id', $id)->whereIn('status', ['PENDING', 'APPROVED'])->lockForUpdate()->first();
            if ($existing?->expired()) {
                $this->expire($existing, $user);
                $existing = null;
            }
            if ($existing) {
                return $existing;
            }
            $reset = new PasswordResetRequest;
            $reset->forceFill(['user_id' => $id, 'status' => 'PENDING', 'requested_at' => now()])->save();
            AuthAudit::record('user.recovery_requested', $user, $actor, ['request_id' => $reset->id]);

            return $reset;
        });
    }

    public function review(PasswordResetRequest $request, User $actor, string $decision, bool $verified, #[\SensitiveParameter] string $password): ?string
    {
        $result = DB::transaction(function () use ($request, $actor, $decision, $verified, $password): array {
            $users = $this->lockUsers([$actor->id, $request->user_id]);
            $admin = $users[$actor->id];
            $user = $users[$request->user_id];
            $this->authorize($admin, $user, $actor);
            abort_unless($this->eligible($user), 422, 'Recovery is available only for active staff accounts.');
            $reset = PasswordResetRequest::query()->lockForUpdate()->findOrFail($request->id);
            abort_unless($reset->status === PasswordResetStatus::Pending, 409, 'This request has already been reviewed.');
            if (! Hash::check($password, $admin->password_hash)) {
                AuthAudit::record('user.recovery_review_failed', $user, $admin, ['request_id' => $reset->id, 'reason' => 'reauthentication']);

                return ['error' => ['admin_password' => 'The administrator password is incorrect.']];
            }
            if ($decision === 'approve' && ! $verified) {
                AuthAudit::record('user.recovery_review_failed', $user, $admin, ['request_id' => $reset->id, 'reason' => 'identity_unverified']);

                return ['error' => ['identity_verified' => 'Confirm personal identity verification before approving recovery.']];
            }
            abort_unless(in_array($decision, ['approve', 'reject'], true), 422);
            $code = $decision === 'approve' ? rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=') : null;
            $reset->forceFill(['status' => $code ? 'APPROVED' : 'REJECTED', 'reviewed_by' => $admin->id, 'reviewed_at' => now(),
                'identity_verified_at' => $code ? now() : null, 'recovery_code_hash' => $code ? Hash::make($code) : null,
                'recovery_expires_at' => $code ? now()->addMinutes(15) : null])->save();
            if ($code) {
                AuthAudit::record('user.recovery_identity_verified', $user, $admin, ['request_id' => $reset->id]);
            }
            AuthAudit::record($code ? 'user.recovery_approved' : 'user.recovery_rejected', $user, $admin, ['request_id' => $reset->id]);
            if ($code) {
                AuthAudit::record('user.recovery_code_issued', $user, $admin, ['request_id' => $reset->id]);
            }

            return ['code' => $code];
        });
        if (isset($result['error'])) {
            throw ValidationException::withMessages($result['error']);
        }

        return $result['code'];
    }

    public function complete(string $username, #[\SensitiveParameter] string $code, #[\SensitiveParameter] string $password): bool
    {
        return DB::transaction(function () use ($username, $code, $password): bool {
            $user = User::query()->whereRaw('lower(username) = ?', [Username::normalize($username)])->lockForUpdate()->first();
            if (! $user || ! $this->eligible($user)) {
                return false;
            }
            $reset = PasswordResetRequest::query()->where('user_id', $user->id)->where('status', 'APPROVED')->lockForUpdate()->first();
            if (! $reset) {
                AuthAudit::record('user.recovery_code_failed', $user, null, ['reason' => 'no_approved_request']);

                return false;
            }
            if ($reset->expired()) {
                $this->expire($reset, $user);

                return false;
            }
            if (! Hash::check($code, $reset->recovery_code_hash)) {
                $reset->forceFill(['failed_attempts' => $reset->failed_attempts + 1])->save();
                AuthAudit::record('user.recovery_code_failed', $user, null, ['request_id' => $reset->id]);
                if ($reset->failed_attempts >= 5) {
                    $this->expire($reset, $user);
                }

                return false;
            }
            $reset->forceFill(['status' => 'COMPLETED', 'completed_at' => now(), 'recovery_code_hash' => null])->save();
            $this->credentials->replaceLocked($user, $password, LoginEvent::PasswordReset);
            AuthAudit::record('user.recovery_completed', $user, null, ['request_id' => $reset->id]);

            return true;
        });
    }

    public function expireDue(): void
    {
        PasswordResetRequest::query()->where('status', 'APPROVED')->where('recovery_expires_at', '<=', now())
            ->select(['id', 'user_id'])->chunkById(100, function ($requests): void {
                foreach ($requests as $request) {
                    DB::transaction(function () use ($request): void {
                        $user = User::query()->lockForUpdate()->findOrFail($request->user_id);
                        $reset = PasswordResetRequest::query()->lockForUpdate()->findOrFail($request->id);
                        if ($reset->expired()) {
                            $this->expire($reset, $user);
                        }
                    });
                }
            });
    }

    private function expire(PasswordResetRequest $reset, User $user): void
    {
        $reset->forceFill(['status' => 'EXPIRED', 'recovery_code_hash' => null])->save();
        AuthAudit::record('user.recovery_expired', $user, null, ['request_id' => $reset->id]);
    }

    private function lockUsers(array $ids): Collection
    {
        return User::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->with('role')->get()->keyBy('id');
    }

    private function authorize(User $admin, User $target, User $sessionUser): void
    {
        Gate::forUser($admin)->authorize('manageAccess', $target);
        abort_unless($admin->credential_version === $sessionUser->credential_version, 403);
    }

    private function eligible(User $user): bool
    {
        return $user->is_active && $user->isOscaStaff();
    }
}
