<?php

namespace App\Http\Requests\SeniorCitizens;

use App\Models\SeniorCitizen;
use App\Services\SeniorCitizens\SeniorRegistrationInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSeniorCitizenRequest extends FormRequest
{
    use RepresentativeRules;

    protected function prepareForValidation(): void
    {
        $this->normalizeContacts();
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create', SeniorCitizen::class) ?? false;
    }

    public function rules(): array
    {
        return SeniorRegistrationInput::rules($this->all());
    }

    public function after(): array
    {
        return [fn (Validator $validator) => SeniorRegistrationInput::checkDuplicate($validator, $this->all())];
    }

    public function messages(): array
    {
        return SeniorRegistrationInput::messages();
    }
}
