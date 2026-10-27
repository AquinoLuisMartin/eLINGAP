<?php

namespace App\Http\Controllers\Administration;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\StoreUserRequest;
use App\Http\Requests\Administration\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\PasswordResetRequest;
use App\Models\Role;
use App\Models\User;
use App\Rules\NewPassword;
use App\Services\Auth\AccountAccess;
use App\Services\Auth\AuthAudit;
use App\Services\Auth\Credentials;
use App\Services\Auth\Username;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'in:ADMIN,OSCA_STAFF'],
        ]);

        $users = User::query()
            ->select(['id', 'role_id', 'username', 'first_name', 'middle_name', 'last_name', 'name_suffix', 'is_active', 'created_at', 'last_login_at'])
            ->with('role')
            ->when($request->filled('search'), fn ($query) => $query->search($request->string('search')->toString()))
            ->when($request->filled('role'), fn ($query) => $query->whereRelation('role', 'name', $request->string('role')->toString()))
            ->orderBy('last_name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('administration.users.index', [
            'users' => $users,
            'roles' => UserRole::cases(),
            'pendingRecoveryRequests' => PasswordResetRequest::query()->where('status', 'PENDING')->count(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('administration.users.create', ['roles' => Role::where('name', UserRole::OscaStaff->value)->get()]);
    }

    public function store(StoreUserRequest $request): View
    {
        abort_if($request->expectsJson(), 406, 'Use the account creation form for private credential handover.');
        $password = Str::random(24);
        try {
            $user = DB::transaction(function () use ($request, $password): User {
                $admin = User::query()->lockForUpdate()->findOrFail($request->user()->id);
                Gate::forUser($admin)->authorize('create', User::class);
                abort_unless($admin->credential_version === $request->user()->credential_version, 403);
                $user = new User($request->validated());
                validator(['password' => $password], ['password' => [new NewPassword($user)]])->validate();
                $user->forceFill(['password_hash' => $password, 'must_change_password' => true])->save();
                AuthAudit::record('user.created', $user, $admin);

                return $user;
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (! str_contains($exception->getMessage(), 'uq_users_username')) {
                throw $exception;
            }
            throw ValidationException::withMessages(['username' => 'This username is already in use. Request a new suggestion or choose another username.']);
        }

        return view('administration.users.credential', ['username' => $user->username, 'credential' => $password,
            'credentialLabel' => 'Temporary password', 'handoverMessage' => 'Account created. Hand this temporary password directly to the verified employee. The employee must change it before using the system.']);
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('administration.users.edit', [
            'user' => $user,
            'roles' => Role::where('name', UserRole::OscaStaff->value)->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, Credentials $credentials, AccountAccess $access): RedirectResponse
    {
        try {
            $user = DB::transaction(function () use ($request, $user, $credentials, $access): User {
                $user = $access->lockTarget($request->user(), $user, 'update');
                if ($user->role_id !== $request->integer('role_id')) {
                    Gate::authorize('manageAccess', $user);
                }

                $oldRole = $user->role_id;
                $user->update($request->validated());
                if ($user->wasChanged('username')) {
                    $credentials->revokeLocked($user, $request->user());
                }
                if ($user->wasChanged()) {
                    AuditLog::create(['user_id' => $request->user()->id, 'action' => 'user.updated', 'auditable_type' => User::class, 'auditable_id' => $user->id, 'old_values' => ['role_id' => $oldRole], 'new_values' => ['role_id' => $user->role_id], 'ip_address' => $request->ip()]);
                }

                return $user;
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (! str_contains($exception->getMessage(), 'uq_users_username')) {
                throw $exception;
            }
            throw ValidationException::withMessages(['username' => 'This username is already in use. Choose another username.']);
        }

        return redirect()->route('administration.users.index')
            ->with('status', "Account {$user->username} updated.");
    }

    public function suggestUsername(Request $request, Username $usernames): JsonResponse
    {
        Gate::authorize('create', User::class);
        $input = $request->validate(['first_name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100']]);

        return response()->json(['username' => $usernames->suggestion($input['first_name'], $input['last_name'])]);
    }
}
