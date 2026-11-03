<?php

namespace App\Http\Requests\Administration;

use App\Enums\UserRole;
use App\Rules\EmployeeIdInput;
use App\Services\Auth\EmployeeId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('user')) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')->where('name', UserRole::OscaStaff->value)],
            'employee_id' => ['required', 'string', new EmployeeIdInput($user->id)],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'name_suffix' => ['nullable', 'string', 'max:20'],
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
