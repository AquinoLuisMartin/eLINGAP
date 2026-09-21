<?php

namespace App\Http\Requests\Applications;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Application::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'senior_citizen_id' => ['required', 'integer', 'exists:senior_citizens,id'],
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'applied_on' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
