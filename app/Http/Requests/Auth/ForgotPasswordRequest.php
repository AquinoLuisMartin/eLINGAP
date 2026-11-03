<?php

namespace App\Http\Requests\Auth;

use App\Services\Auth\EmployeeId;
use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return ['employee_id' => ['required', 'string', 'max:100', 'not_regex:/[\x00-\x1F\x7F]/u']];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('employee_id'))) {
            $this->merge(['employee_id' => EmployeeId::normalize($this->input('employee_id'))]);
        }
    }
}
