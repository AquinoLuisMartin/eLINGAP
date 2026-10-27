<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentCredentials
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $request->routeIs('logout')) {
            return $next($request);
        }
        if (! $request->session()->has('credential_version') && Auth::guard()->viaRemember()) {
            $request->session()->put('credential_version', $user->credential_version);
        }
        if ($request->session()->get('credential_version') !== $user->credential_version) {
            Auth::logoutCurrentDevice();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }
        if ($user->must_change_password && ! $request->routeIs('password.change', 'password.change.update')) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
