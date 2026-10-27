<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\Auth\PasswordRecovery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Timebox;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function createRequest(): View
    {
        return view('auth.forgot-password');
    }

    public function sendRequest(ForgotPasswordRequest $request, PasswordRecovery $recovery, Timebox $timebox): RedirectResponse
    {
        $timebox->call(fn () => $recovery->request($request->validated('username')), 200000);

        return back()->with('status', PasswordRecovery::CONFIRMATION);
    }

    public function createReset(): View
    {
        return view('auth.reset-password');
    }

    public function reset(ResetPasswordRequest $request, PasswordRecovery $recovery, Timebox $timebox): RedirectResponse
    {
        $completed = $timebox->call(fn () => $recovery->complete($request->validated('username'),
            $request->validated('recovery_code'), $request->validated('password')), 200000);
        if (! $completed) {
            throw ValidationException::withMessages(['recovery_code' => PasswordRecovery::INVALID_CODE]);
        }

        return redirect()->route('login')->with('status', 'Password reset. Sign in with your new password.');
    }
}
