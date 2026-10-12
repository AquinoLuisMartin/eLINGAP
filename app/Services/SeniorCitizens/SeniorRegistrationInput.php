<?php

namespace App\Services\SeniorCitizens;

use App\Models\SeniorCitizen;
use App\Services\Sms\SmsText;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator as InputValidator;

class SeniorRegistrationInput
{
    public static function normalize(array $input): array
    {
        foreach (['contact_number', 'representative_contact'] as $field) {
            if (is_string($input[$field] ?? null) && $number = SmsText::mobile($input[$field])) {
                $input[$field] = $number;
            }
        }

        return $input;
    }

    public static function representativeRules(array $input, ?SeniorCitizen $senior = null): array
    {
        $representative = ($input['applicant_type'] ?? 'SELF') === 'REPRESENTATIVE' || filled($input['representative_contact'] ?? null) || filled($input['representative_name'] ?? null);
        $changed = ! $senior || collect(['representative_name', 'representative_relationship', 'representative_contact'])->contains(fn ($field) => ($input[$field] ?? null) !== $senior->{$field});
        $proofRequired = $representative && ($changed || ! $senior?->documents()->where('document_type', 'AUTHORIZATION')->exists());

        return [
            'contact_number' => ['nullable', 'required_without:representative_contact', 'string', 'regex:/^\+639\d{9}$/'],
            'applicant_type' => ['sometimes', 'required', Rule::in(['SELF', 'REPRESENTATIVE'])],
            'representative_name' => [Rule::requiredIf($representative), 'nullable', 'string', 'max:180'],
            'representative_relationship' => [Rule::requiredIf($representative), 'nullable', 'string', 'max:100'],
            'representative_contact' => [Rule::requiredIf($representative), 'nullable', 'string', 'regex:/^\+639\d{9}$/'],
            'authorization_file' => [Rule::requiredIf($proofRequired), 'nullable', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(5120)],
        ];
    }

    public static function rules(array $input): array
    {
        return [
            'barangay_id' => ['required', 'integer', Rule::exists('barangays', 'id')->where('is_active', true)],
            'first_name' => ['required', 'string', 'max:100'], 'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'], 'name_suffix' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.today()->subYears(60)->toDateString()],
            'sex' => ['required', 'string', 'in:MALE,FEMALE,OTHER'], 'civil_status' => ['nullable', 'string', 'in:SINGLE,MARRIED,WIDOWED'],
            'address' => ['required', 'string', 'max:1000'],
            'osca_id_number' => ['missing'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png', 'dimensions:ratio=1/1', 'max:2048'],
            'identification_file' => ['nullable', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(5120)],
            ...self::representativeRules($input),
        ];
    }

    public static function checkDuplicate(InputValidator $validator, array $input): void
    {
        if ($validator->errors()->hasAny(['first_name', 'last_name', 'birth_date'])) {
            return;
        }
        $duplicate = SeniorCitizen::query()->where('birth_date', $input['birth_date'])
            ->whereRaw('lower(first_name) = ?', [mb_strtolower($input['first_name'])])
            ->whereRaw('lower(last_name) = ?', [mb_strtolower($input['last_name'])])->exists();
        if ($duplicate) {
            $validator->errors()->add('first_name', 'A senior citizen with this name and birth date already exists.');
        }
    }

    public static function validator(array $input, ?array $fields = null): InputValidator
    {
        $rules = self::rules($input);
        if ($fields !== null) {
            $rules = array_intersect_key($rules, array_flip($fields));
        }
        $validator = Validator::make($input, $rules, self::messages());
        if ($fields === null || in_array('first_name', $fields)) {
            $validator->after(fn ($validator) => self::checkDuplicate($validator, $input));
        }

        return $validator;
    }

    public static function messages(): array
    {
        return [
            'photo.max' => 'The photo field must not be greater than 2MB.',
            'birth_date.before_or_equal' => 'The applicant must be at least 60 years old on the application date.',
        ];
    }
}
