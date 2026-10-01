<?php

namespace App\Http\Controllers\Auth;

use App\Enums\LoginEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\LoginLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, LoginLogger $logger): RedirectResponse
    {
        if (! $request->attemptLogin()) {
            $logger->failure(LoginEvent::LoginFailed, $request->identity(), 'Invalid credentials or inactive account.');

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->save();

        $logger->success(LoginEvent::Login, $user);

        $user->loadMissing('role');

        return redirect()->route($user->homeRouteName());
    }

    public function destroy(Request $request, LoginLogger $logger): RedirectResponse
    {
        $logger->success(LoginEvent::Logout, $request->user());

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
