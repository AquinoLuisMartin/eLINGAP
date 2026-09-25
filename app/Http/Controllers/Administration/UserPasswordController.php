<?php

namespace App\Http\Controllers\Administration;

use App\Enums\LoginEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\UpdateUserPasswordRequest;
use App\Models\User;
use App\Services\Auth\LoginLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class UserPasswordController extends Controller
{
    public function update(UpdateUserPasswordRequest $request, User $user, LoginLogger $logger): RedirectResponse
    {
        DB::transaction(function () use ($request, $user, $logger): void {
            $user->forceFill([
                'password_hash' => $request->validated('password'),
                'remember_token' => null,
            ])->save();

            $logger->success(LoginEvent::PasswordReset, $user);
        });

        return back()->with('status', "Password for {$user->username} was reset.");
    }
}
