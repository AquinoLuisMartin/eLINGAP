<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trimStrings(except: ['admin_password', 'passwordForm.current', 'passwordForm.next', 'passwordForm.confirm']);
        $middleware->web(append: [SecurityHeaders::class]);
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['current_password', 'password', 'password_confirmation', 'token', 'admin_password', 'recovery_code', 'cf-turnstile-response']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            if ($response->isServerError() && $request->routeIs('payout-schedules.create')) {
                $cause = $exception;
                while ($cause->getPrevious() !== null) {
                    $cause = $cause->getPrevious();
                }

                $applicationFrame = null;
                foreach ($cause->getTrace() as $frame) {
                    if (str_starts_with($frame['file'] ?? '', app_path().DIRECTORY_SEPARATOR)) {
                        $applicationFrame = basename($frame['file']).':'.$frame['line'];
                        break;
                    }
                }

                rescue(fn () => Log::error('Payout schedule page failed.', [
                    'failure_type' => $cause::class,
                    'origin' => basename($cause->getFile()).':'.$cause->getLine(),
                    'application_frame' => $applicationFrame,
                    'sqlstate' => $cause instanceof PDOException ? ($cause->errorInfo[0] ?? null) : null,
                ]), report: false);
            }

            return $response;
        });
    })->create();
