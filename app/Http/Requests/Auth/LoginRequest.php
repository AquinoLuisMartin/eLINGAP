<?php

namespace App\Http\Requests\Auth;

use App\Rules\PasswordInput;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            'username' => ['required', 'string', 'max:100', 'not_regex:/[\x00-\x1F\x7F]/u'],
            'password' => ['required', 'string', new PasswordInput],
            'remember' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Enter your assigned username.',
        ];
    }

    // Authenticate only the assigned username through Laravel's session guard.
    public function attemptLogin(): bool
    {
        $this->ensureIsNotRateLimited();

        $credentials = [
            // Match the stored username without changing legacy spellings.
            'username' => function (Builder $query): void {
                $query->whereRaw('lower(username) = ?', [$this->identity()])->lockForUpdate();
            },
            // Deactivated accounts must not authenticate even with a valid password.
            'is_active' => true,
            'password' => $this->string('password')->toString(),
        ];

        if (! DB::transaction(fn () => Auth::attempt($credentials, $this->boolean('remember')))) {
            RateLimiter::hit($this->throttleKey());

            return false;
        }

        RateLimiter::clear($this->throttleKey());

        return true;
    }

    public function identity(): string
    {
        return Str::lower(trim($this->string('username')->toString()));
    }

    // Limit failures across addresses without storing a readable username in cache.
    protected function throttleKey(): string
    {
        return 'login:'.hash_hmac('sha256', $this->identity(), config('app.key'));
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
