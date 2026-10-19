<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\UpdateUserPasswordRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class UserPasswordController extends Controller
{
    public function update(UpdateUserPasswordRequest $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user): void {
            $user->forceFill([
                'password_hash' => $request->validated('password'),
                'remember_token' => null,
            ])->save();

            AuditLog::create(['user_id' => $request->user()->id, 'action' => 'user.password_reset', 'auditable_type' => User::class, 'auditable_id' => $user->id, 'ip_address' => $request->ip()]);
        });

        return back()->with('status', "Password for {$user->username} was reset.");
    }
}
