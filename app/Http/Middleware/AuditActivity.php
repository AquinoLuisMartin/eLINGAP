<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);
        } finally {
            if (auth()->check()) {
                $record = collect($request->route()?->parameters() ?? [])->first(fn ($value) => $value instanceof Model);
                AuditLog::create(['user_id' => auth()->id(), 'action' => mb_substr('access.'.($request->route()?->getName() ?? 'page'), 0, 80), 'auditable_type' => $record ? $record::class : null, 'auditable_id' => $record?->getKey(), 'new_values' => ['method' => $request->method()], 'ip_address' => $request->ip()]);
            }
        }

        return $response;
    }
}
