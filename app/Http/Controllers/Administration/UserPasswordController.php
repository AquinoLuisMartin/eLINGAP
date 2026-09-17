<?php

namespace App\Http\Controllers\Administration;

use App\Enums\LoginEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\UpdateUserPasswordRequest;
use App\Models\User;
use App\Services\Auth\LoginLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class UserPasswordController extends Controller
{
    public function update(UpdateUserPasswordRequest $request, User $user, LoginLogger $logger): RedirectResponse
    {
        Gate::authorize('update', $user);

        // Clearing the token stops any remembered session from surviving the reset.
        $user->forceFill([
            'password_hash' => $request->string('password')->toString(),
            'remember_token' => null,
        ])->save();

        $logger->success(LoginEvent::PasswordReset, $user);

        return back()->with('status', "Password for {$user->username} was reset.");
    }
}
