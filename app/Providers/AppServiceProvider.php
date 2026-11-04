<?php

namespace App\Providers;

use App\Hashing\PasswordHasher;
use App\Http\Middleware\AuditActivity;
use App\Http\Middleware\EnsureCurrentCredentials;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Services\Auth\EmployeeId;
use App\View\Composers\OfficeContent;
use App\View\Composers\PublicContent;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        View::composer('welcome', PublicContent::class);
        View::composer([
            'welcome', 'layouts.app', 'auth._login-dialog', 'components.about-elingap',
            'senior-citizens.show', 'senior-citizens.transfers.panel', 'senior-citizens.transfers.certificate',
            'senior-citizens.ids.verify', 'livewire.senior-citizens.id-manager',
        ], OfficeContent::class);
        Hash::extend('argon2id', fn () => new PasswordHasher(config('hashing.argon', [])));
        Password::defaults(fn () => Password::min(15)->max(128));
        foreach (['recovery-request' => [3, 60, 5], 'recovery-complete' => [5, 1, 20]] as $name => [$attempts, $minutes, $ipAttempts]) {
            RateLimiter::for($name, function ($request) use ($attempts, $minutes, $ipAttempts) {
                $employeeId = is_string($request->input('employee_id')) ? EmployeeId::normalize($request->input('employee_id')) : '';

                return [Limit::perMinute($ipAttempts)->by('ip:'.hash_hmac('sha256', $request->ip(), config('app.key'))),
                    Limit::perMinutes($minutes, $attempts)->by('account:'.hash_hmac('sha256', $employeeId, config('app.key')))];
            });
        }

        Model::shouldBeStrict(! $this->app->isProduction());

        Livewire::addPersistentMiddleware([
            AuditActivity::class,
            AuthenticateSession::class,
            EnsureUserIsActive::class,
            EnsureCurrentCredentials::class,
            EnsureUserHasRole::class,
        ]);
    }
}
