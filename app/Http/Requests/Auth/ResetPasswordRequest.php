<?php

namespace App\Http\Requests\Auth;

use App\Rules\PasswordInput;

class ResetPasswordRequest extends ForgotPasswordRequest
{
    public function rules(): array
    {
        return [...parent::rules(), 'recovery_code' => ['required', 'string', 'regex:/\A[A-Za-z0-9_-]{43}\z/'],
            'password' => ['required', 'string', 'confirmed', new PasswordInput]];
    }

    public function messages(): array
    {
        return ['recovery_code.regex' => 'Enter the recovery code exactly as provided by your administrator.'];
    }
}
