<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UserStatusController extends Controller
{
    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('manageAccess', $user);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $user = DB::transaction(function () use ($request, $user, $validated): User {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $wasActive = $user->is_active;
            $user->update($validated);
            if ($user->wasChanged('is_active')) {
                AuditLog::create(['user_id' => $request->user()->id, 'action' => 'user.access_changed', 'auditable_type' => User::class, 'auditable_id' => $user->id, 'old_values' => ['is_active' => $wasActive], 'new_values' => ['is_active' => $user->is_active], 'ip_address' => $request->ip()]);
            }

            return $user;
        });

        return back()->with('status', $user->is_active
            ? "Account {$user->username} activated."
            : "Account {$user->username} deactivated.");
    }
}
