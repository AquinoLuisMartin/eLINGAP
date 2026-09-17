<?php

namespace App\Http\Controllers\Administration;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\StoreUserRequest;
use App\Http\Requests\Administration\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $users = User::query()
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
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('administration.users.create', ['roles' => Role::orderBy('name')->get()]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([
            ...$request->safe()->except('password'),
            'password_hash' => $request->string('password')->toString(),
        ]);

        return redirect()->route('administration.users.index')
            ->with('status', "Account {$user->username} created.");
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('administration.users.edit', [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        // Changing your own role could remove your administrator access.
        if ($user->role_id !== $request->integer('role_id')) {
            Gate::authorize('manageAccess', $user);
        }

        $user->update($request->validated());

        return redirect()->route('administration.users.index')
            ->with('status', "Account {$user->username} updated.");
    }
}
