<?php

namespace App\Http\Requests\Administration;

use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', User::class) ?? false;
    }

    public function rules(): array
    {
        $rules = ['settings' => ['required', 'array:'.implode(',', SystemConfiguration::OFFICE_KEYS)]];
        foreach (SystemConfiguration::OFFICE_KEYS as $key) {
            $rules['settings.'.$key] = ['sometimes', 'nullable', 'string', 'max:'.($key === 'office_address' ? 1000 : 180)];
        }
        $rules['settings.support_email'][] = 'email';

        return $rules;
    }
}
