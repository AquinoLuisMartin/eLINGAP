<?php

namespace App\Http\Requests\Administration;

use App\Enums\UserRole;
use App\Models\User;
use App\Rules\EmployeeIdInput;
use App\Services\Auth\EmployeeId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')->where('name', UserRole::OscaStaff->value)],
            'employee_id' => ['required', 'string', new EmployeeIdInput],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'name_suffix' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ];
    }

    // Normalize login identifiers before validating uniqueness.
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('employee_id'))) {
            $this->merge(['employee_id' => EmployeeId::normalize($this->input('employee_id'))]);
        }
    }
}
