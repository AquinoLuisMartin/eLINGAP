<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Rules\PasswordInput;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
            'email' => ['required', 'string', 'max:255', Rule::when(
                fn () => is_string($this->input('email')) && str_contains($this->input('email'), '@'),
                ['email:rfc'],
                ['regex:/^[a-z0-9._-]+(?: [a-z0-9._-]+)*$/i'],
            )],
            'password' => ['required', 'string', new PasswordInput],
            'remember' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.regex' => 'Enter your exact username using letters, numbers, dots, underscores, dashes, and any spaces recorded in your username.',
            'email.email' => 'Enter a valid email address.',
        ];
    }

    /**
     * Attempt to sign the user in by email or username.
     */
    public function attemptLogin(): bool
    {
        $this->ensureIsNotRateLimited();

        $credentials = [
            // Case-insensitive match on email or username.
            'email' => function (Builder $query): void {
                $identity = $this->identity();

                $query->where(function (Builder $query) use ($identity): void {
                    $query->whereRaw('lower(email) = ?', [$identity])
                        ->orWhereRaw('lower(username) = ?', [$identity]);
                });
            },
            // Deactivated accounts must not authenticate even with a valid password.
            'is_active' => true,
            'password' => $this->string('password')->toString(),
        ];

        if (! Auth::attemptWhen($credentials, function (User $user): bool {
            $identity = $this->identity();

            return Str::lower($user->username) === $identity
                || ($user->email !== null && Str::lower($user->email) === $identity);
        }, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            return false;
        }

        RateLimiter::clear($this->throttleKey());

        return true;
    }

    public function identity(): string
    {
        return Str::lower(trim($this->string('email')->toString()));
    }

    /**
     * Keying on both identity and address avoids locking out a shared office network.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate($this->identity().'|'.$this->ip());
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }
}
