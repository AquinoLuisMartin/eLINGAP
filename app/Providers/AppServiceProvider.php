<?php

namespace App\Providers;

use App\Hashing\PasswordHasher;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Hash;
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
        Hash::extend('bcrypt', fn () => new PasswordHasher(config('hashing.bcrypt', [])));
        Password::defaults(fn () => Password::min(12));
        ResetPassword::createUrlUsing(fn ($user, string $token): string => rtrim(config('app.url'), '/')
            .route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()], false));

        Model::shouldBeStrict(! $this->app->isProduction());

        Livewire::addPersistentMiddleware([
            AuthenticateSession::class,
            EnsureUserIsActive::class,
            EnsureUserHasRole::class,
        ]);
    }
}
