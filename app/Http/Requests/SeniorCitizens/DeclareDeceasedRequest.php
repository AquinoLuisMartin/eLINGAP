<?php

namespace App\Http\Requests\SeniorCitizens;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class DeclareDeceasedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('declareDeceased', $this->route('senior_citizen')) ?? false;
    }

    public function rules(): array
    {
        $senior = $this->route('senior_citizen');

        return [
            'died_on' => ['required', 'date', 'after_or_equal:'.$senior->birth_date->format('Y-m-d'), 'before_or_equal:today'],
            'confirmation' => ['required', 'string', Rule::in([$senior->osca_id_number ?: $senior->registration_number])],
            'death_document' => ['required', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(5 * 1024)],
        ];
    }
}
