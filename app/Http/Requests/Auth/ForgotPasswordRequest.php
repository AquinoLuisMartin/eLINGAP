<?php

namespace App\Http\Requests\Auth;

use App\Services\Auth\Username;
use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return ['username' => ['required', 'string', 'max:100', 'not_regex:/[\x00-\x1F\x7F]/u']];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('username'))) {
            $this->merge(['username' => Username::normalize($this->input('username'))]);
        }
    }
}
