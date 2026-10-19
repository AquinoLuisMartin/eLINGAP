<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuditActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') || ! $request->routeIs('senior-citizens.document')) {
            return $next($request);
        }

        try {
            $response = $next($request);
        } finally {
            if (auth()->check()) {
                try {
                    $record = collect($request->route()?->parameters() ?? [])->first(fn ($value) => $value instanceof Model);
                    AuditLog::create(['user_id' => auth()->id(), 'action' => mb_substr('access.'.($request->route()?->getName() ?? 'page'), 0, 80), 'auditable_type' => $record ? $record::class : null, 'auditable_id' => $record?->getKey(), 'new_values' => ['method' => $request->method()], 'ip_address' => $request->ip()]);
                } catch (Throwable $exception) {
                    try {
                        Log::error('Page access audit could not be recorded.', ['failure_type' => $exception::class]);
                    } catch (Throwable) {
                        error_log('Page access audit and application logging are unavailable.');
                    }
                }
            }
        }

        return $response;
    }
}
