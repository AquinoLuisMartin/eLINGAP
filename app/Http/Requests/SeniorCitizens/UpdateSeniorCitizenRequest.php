<?php

namespace App\Http\Requests\SeniorCitizens;

use App\Models\SeniorCitizen;
use App\Services\Sms\SmsText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSeniorCitizenRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('contact_number')) && $mobile = SmsText::mobile($this->input('contact_number'))) {
            $this->merge(['contact_number' => $mobile]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('senior_citizen')) ?? false;
    }

    public function rules(): array
    {
        $seniorCitizen = $this->route('senior_citizen');

        return [
            'barangay_id' => ['required', 'integer', Rule::exists('barangays', 'id')->where('is_active', true)],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'name_suffix' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['required', 'date', 'before_or_equal:'.now()->subYears(60)->toDateString()],
            'sex' => ['required', 'string', 'in:MALE,FEMALE,OTHER'],
            'civil_status' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
            'contact_number' => ['nullable', 'string', 'regex:/^\+639\d{9}$/'],
            'osca_id_number' => ['nullable', 'string', 'max:50', 'unique:senior_citizens,osca_id_number,'.$seniorCitizen?->id],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png', 'dimensions:ratio=1/1', 'max:2048'],
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
                ->whereNotIn('status', ['ARCHIVED', 'DECEASED'])->exists();
            if ($duplicate) {
                $validator->errors()->add('first_name', 'A senior citizen with this name and birth date already exists.');
            }
        }];
    }
}
