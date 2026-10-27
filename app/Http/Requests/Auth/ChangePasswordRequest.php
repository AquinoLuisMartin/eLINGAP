<?php

namespace App\Http\Requests\Auth;

use App\Rules\PasswordInput;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active ?? false;
    }

    public function rules(): array
    {
        return ['current_password' => ['required', 'string', new PasswordInput],
            'password' => ['required', 'string', 'confirmed', new PasswordInput]];
    }
}
