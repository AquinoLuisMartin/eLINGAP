<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\AccountAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserStatusController extends Controller
{
    public function update(Request $request, User $user, AccountAccess $access): RedirectResponse
    {
        Gate::authorize('manageAccess', $user);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $user = $access->setStatus($request->user(), $user, (bool) $validated['is_active']);

        return back()->with('status', $user->is_active
            ? "Account {$user->username} activated."
            : "Account {$user->username} deactivated.");
    }
}
