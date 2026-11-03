<?php

namespace App\Http\Requests\Auth;

use App\Rules\PasswordInput;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
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
            'employee_id' => ['required', 'string', 'max:100', 'not_regex:/[\x00-\x1F\x7F]/u'],
            'password' => ['required', 'string', new PasswordInput],
            'remember' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Enter your assigned employee ID.',
        ];
    }

    // Authenticate only the assigned employee ID through Laravel's session guard.
    public function attemptLogin(): bool
    {
        $this->ensureIsNotRateLimited();

        $credentials = [
            'employee_id' => function (Builder $query): void {
                $query->where('employee_id', $this->identity())->lockForUpdate();
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
        return trim($this->string('employee_id')->toString());
    }

    // Limit failures across addresses without storing a readable employee ID in cache.
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
            'employee_id' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }
}
