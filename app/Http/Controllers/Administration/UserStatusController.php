<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserStatusController extends Controller
{
    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manageAccess', $user);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $user->update($validated);

        return back()->with('status', $user->is_active
            ? "Account {$user->username} activated."
            : "Account {$user->username} deactivated.");
    }
}
