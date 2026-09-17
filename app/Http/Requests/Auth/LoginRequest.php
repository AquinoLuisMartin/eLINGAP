<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    private const MAX_ATTEMPTS = 5;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }

    /**
     * Attempt to sign the user in, reporting whether the credentials were accepted.
     */
    public function attemptLogin(): bool
    {
        $this->ensureIsNotRateLimited();

        $credentials = [
            // Matches the lower(username) index so sign-in is case-insensitive.
            'username' => fn (Builder $query) => $query->whereRaw('lower(username) = ?', [$this->username()]),
            // Deactivated accounts must not authenticate even with a valid password.
            'is_active' => true,
            'password' => $this->string('password')->toString(),
        ];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            return false;
        }

        RateLimiter::clear($this->throttleKey());

        return true;
    }

    public function username(): string
    {
        return Str::lower(trim($this->string('username')->toString()));
    }

    /**
     * Keying on both account and address avoids locking out a shared office network.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate($this->username().'|'.$this->ip());
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }
}
