<?php

namespace App\Http\Requests\SeniorCitizens;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSeniorCitizenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('senior_citizen')) ?? false;
    }

    public function rules(): array
    {
        $seniorCitizen = $this->route('senior_citizen');

        return [
            'barangay_id' => ['required', 'integer', 'exists:barangays,id'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'name_suffix' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['required', 'date', 'before_or_equal:'.now()->subYears(60)->toDateString()],
            'sex' => ['required', 'string', 'in:MALE,FEMALE,OTHER'],
            'civil_status' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'osca_id_number' => ['nullable', 'string', 'max:50', 'unique:senior_citizens,osca_id_number,'.$seniorCitizen?->id],
        ];
    }
}
