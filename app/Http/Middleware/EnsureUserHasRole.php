<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueError;

class EnsureUserHasRole
{
    /**
     * Restrict the request to authenticated users who hold one of the given roles.
     *
     * @param  string  ...$roles  Role enum values such as ADMIN or OSCA_STAFF.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        $user->loadMissing('role');

        $allowed = collect($roles)->map(function (string $role): UserRole {
            try {
                return UserRole::from($role);
            } catch (ValueError) {
                abort(500, "Unknown role middleware parameter [{$role}].");
            }
        });

        if ($allowed->contains(fn (UserRole $role): bool => $user->hasRole($role))) {
            return $next($request);
        }

        abort(403);
    }
}
