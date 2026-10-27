<?php

namespace App\Http\Requests\Administration;

use App\Enums\UserRole;
use App\Models\User;
use App\Rules\UsernameInput;
use App\Services\Auth\Username;
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
            'username' => ['required', 'string', new UsernameInput],
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
        if (is_string($this->input('username'))) {
            $this->merge(['username' => Username::normalize($this->input('username'))]);
        }
        if (blank($this->input('username')) && is_string($this->input('first_name')) && is_string($this->input('last_name'))) {
            $this->merge(['username' => app(Username::class)->suggestion($this->input('first_name'), $this->input('last_name'))]);
        }
    }
}
