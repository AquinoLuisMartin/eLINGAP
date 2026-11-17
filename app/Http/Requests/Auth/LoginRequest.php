<?php

namespace App\Http\Requests\Auth;

use App\Rules\PasswordInput;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
            'cf-turnstile-response' => ['required', 'string', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Enter your assigned employee ID.',
            'cf-turnstile-response.required' => 'Complete the security verification before signing in.',
            'cf-turnstile-response.string' => 'Security verification is invalid. Please try again.',
            'cf-turnstile-response.max' => 'Security verification is invalid. Please try again.',
        ];
    }

    // Authenticate only the assigned employee ID through Laravel's session guard.
    public function attemptLogin(): bool
    {
        $this->ensureIsNotRateLimited();
        $this->verifyTurnstile();

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

    private function verifyTurnstile(): void
    {
        $secret = config('services.turnstile.secret_key');
        $valid = false;

        if (is_string($secret) && $secret !== '') {
            try {
                $response = Http::asForm()->connectTimeout(3)->timeout(5)
                    ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                        'secret' => $secret,
                        'response' => $this->input('cf-turnstile-response'),
                    ]);

                $valid = $response->successful()
                    && $response->json('success') === true
                    && $response->json('action') === config('services.turnstile.action', 'login')
                    && in_array($response->json('hostname'), config('services.turnstile.hostnames', []), true);
            } catch (ConnectionException) {
                $valid = false;
            }
        }

        if (! $valid) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'Security verification failed. Please try again.',
            ]);
        }
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
