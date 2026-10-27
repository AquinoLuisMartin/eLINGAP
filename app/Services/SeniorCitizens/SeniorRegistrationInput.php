<?php

namespace App\Services\SeniorCitizens;

use App\Models\SeniorCitizen;
use App\Services\Sms\SmsText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator as InputValidator;

class SeniorRegistrationInput
{
    public const UPPERCASE_FIELDS = ['first_name', 'middle_name', 'last_name', 'name_suffix', 'address', 'representative_name', 'representative_relationship'];

    public static function requirementsRules(array $input): array
    {
        $checked = ($input['requirements_status'] ?? null) === 'CHECKED';
        $representative = ($input['applicant_type'] ?? 'SELF') === 'REPRESENTATIVE' || filled($input['representative_name'] ?? null) || filled($input['representative_contact'] ?? null);

        return [
            'requirements_status' => ['required', Rule::in(['CHECKED', 'REVIEW'])],
            'requirements_checks' => ['required', 'array:identity,residency,authorization'],
            'requirements_checks.identity' => [$checked ? 'accepted' : 'boolean', 'required'],
            'requirements_checks.residency' => [$checked ? 'accepted' : 'boolean', 'required'],
            'requirements_checks.authorization' => [$checked && $representative ? 'accepted' : 'boolean', 'required'],
            'review_reason' => ['exclude_unless:requirements_status,REVIEW', 'required', 'string', 'min:5', 'max:500'],
        ];
    }

    public static function normalize(array $input): array
    {
        foreach (self::UPPERCASE_FIELDS as $field) {
            if (is_string($input[$field] ?? null)) {
                $input[$field] = mb_strtoupper(trim($input[$field]), 'UTF-8');
            }
        }

        foreach (['contact_number', 'representative_contact'] as $field) {
            if (is_string($input[$field] ?? null)) {
                $local = preg_replace('/[\s()-]/', '', $input[$field]);
                $number = preg_match('/^9[0-9]{9}\z/', $local) ? '+63'.$local : SmsText::mobile($input[$field]);
                if ($number !== null) {
                    $input[$field] = $number;
                }
            }
        }

        return $input;
    }

    public static function representativeRules(array $input, ?SeniorCitizen $senior = null): array
    {
        $representative = ($input['applicant_type'] ?? 'SELF') === 'REPRESENTATIVE' || filled($input['representative_contact'] ?? null) || filled($input['representative_name'] ?? null);
        $existing = $senior ? self::normalize($senior->only(['representative_name', 'representative_relationship', 'representative_contact'])) : [];
        $changed = ! $senior || collect(['representative_name', 'representative_relationship', 'representative_contact'])->contains(fn ($field) => ($input[$field] ?? null) !== ($existing[$field] ?? null));
        $proofRequired = $representative && ($changed || ! $senior?->documents()->where('document_type', 'AUTHORIZATION')->exists());

        return [
            'contact_number' => ['required', 'string', 'regex:/^\+639\d{9}$/'],
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
            ...self::requirementsRules($input),
            'barangay_id' => ['required', 'integer', Rule::exists('barangays', 'id')->where('is_active', true)],
            'first_name' => ['required', 'string', 'max:100'], 'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'], 'name_suffix' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.today()->subYears(60)->toDateString()],
            'sex' => ['required', 'string', 'in:MALE,FEMALE,OTHER'], 'civil_status' => ['nullable', 'string', 'in:SINGLE,MARRIED,WIDOWED'],
            'address' => ['required', 'string', 'max:1000'],
            'osca_id_number' => ['missing'],
            'photo' => ['prohibited'],
            'identification_file' => ['nullable', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(5120)],
            ...self::representativeRules($input),
        ];
    }

    public static function checkDuplicate(InputValidator $validator, array $input, ?SeniorCitizen $senior = null): void
    {
        if ($validator->errors()->hasAny(['first_name', 'middle_name', 'last_name', 'name_suffix', 'birth_date'])) {
            return;
        }
        $duplicate = self::identityQuery($input)
            ->when($senior, fn ($query) => $query->whereKeyNot($senior->id))->exists();
        if ($duplicate) {
            $validator->errors()->add('first_name', 'A senior citizen with this name and birth date already exists.');
        }
    }

    public static function identityQuery(array $input): Builder
    {
        return SeniorCitizen::query()->where('birth_date', $input['birth_date'])
            ->whereRaw('lower(first_name) = ?', [mb_strtolower($input['first_name'])])
            ->whereRaw("lower(coalesce(middle_name, '')) = ?", [mb_strtolower(trim($input['middle_name'] ?? ''))])
            ->whereRaw("lower(coalesce(name_suffix, '')) = ?", [mb_strtolower(trim($input['name_suffix'] ?? ''))])
            ->whereRaw('lower(last_name) = ?', [mb_strtolower($input['last_name'])]);
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
            'contact_number.regex' => 'Enter a valid Philippine mobile number: 10 digits starting with 9 after +63.',
            'representative_contact.regex' => 'Enter a valid representative mobile number: 10 digits starting with 9 after +63.',
        ];
    }
}
