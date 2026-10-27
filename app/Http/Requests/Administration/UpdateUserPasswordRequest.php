<?php

namespace App\Http\Requests\Administration;

use App\Models\User;
use App\Rules\PasswordInput;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', User::class) ?? false;
    }

    public function rules(): array
    {
        return ['decision' => ['required', 'in:approve,reject'], 'identity_verified' => ['sometimes', 'boolean'],
            'admin_password' => ['required', 'string', new PasswordInput]];
    }
}
