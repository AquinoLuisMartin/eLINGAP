<?php

namespace App\Http\Requests\Programs;

use Illuminate\Foundation\Http\FormRequest;

class StoreBeneficiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Beneficiary::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'senior_citizen_id' => ['required', 'integer', 'exists:senior_citizens,id'],
            'application_id' => ['nullable', 'integer', 'exists:applications,id'],
            'enrolled_on' => ['required', 'date'],
            'status' => ['sometimes', 'string', 'in:ACTIVE,INACTIVE'],
        ];
    }
}
