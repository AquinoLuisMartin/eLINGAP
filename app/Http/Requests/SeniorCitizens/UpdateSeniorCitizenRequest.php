<?php

namespace App\Http\Requests\SeniorCitizens;

use App\Models\SeniorCitizen;
use App\Services\SeniorCitizens\SeniorRegistrationInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSeniorCitizenRequest extends FormRequest
{
    use RepresentativeRules;

    protected function prepareForValidation(): void
    {
        $this->normalizeContacts();
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
            'contact_number' => ['nullable', 'string', 'regex:/^\+639\d{9}$/'],
            'osca_id_number' => ['missing'],
            'photo' => [Rule::prohibitedIf(! $this->user()->can('manageId', $this->route('senior_citizen'))), 'nullable', 'image', 'mimes:jpeg,png', 'dimensions:max_width=6000,max_height=6000', 'max:2048'],
            ...$this->representativeRules(),
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['first_name', 'last_name', 'birth_date'])) {
                return;
            }
            $duplicate = SeniorCitizen::query()->where('id', '!=', $this->route('senior_citizen')->id)
                ->where('birth_date', $this->input('birth_date'))
                ->whereRaw('lower(first_name) = ?', [mb_strtolower($this->input('first_name'))])
                ->whereRaw('lower(last_name) = ?', [mb_strtolower($this->input('last_name'))])
                ->exists();
            if ($duplicate) {
                $validator->errors()->add('first_name', 'A senior citizen with this name and birth date already exists.');
            }
        }];
    }

    public function messages(): array
    {
        return SeniorRegistrationInput::messages();
    }
}
