<?php

namespace App\Http\Requests\SeniorCitizens;

use App\Services\SeniorCitizens\SeniorRegistrationInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSeniorCitizenRequest extends FormRequest
{
    use RepresentativeRules;

    protected function prepareForValidation(): void
    {
        $this->normalizeRegistrationInput();
    }

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('senior_citizen')) ?? false;
    }

    public function rules(): array
    {
        return [
            'barangay_id' => ['required', 'integer', Rule::exists('barangays', 'id')->where('is_active', true)],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'name_suffix' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['required', 'date', 'before_or_equal:'.now()->subYears(60)->toDateString()],
            'sex' => ['required', 'string', 'in:MALE,FEMALE,OTHER'],
            'civil_status' => ['nullable', 'string', 'in:SINGLE,MARRIED,WIDOWED'],
            'address' => ['required', 'string', 'max:1000'],
            'contact_number' => ['required', 'string', 'regex:/^\+639\d{9}$/'],
            'osca_id_number' => ['missing'],
            'photo' => ['prohibited'],
            ...$this->representativeRules(),
        ];
    }

    public function after(): array
    {
        return [fn (Validator $validator) => SeniorRegistrationInput::checkDuplicate($validator, $this->all(), $this->route('senior_citizen'))];
    }

    public function messages(): array
    {
        return SeniorRegistrationInput::messages();
    }
}
