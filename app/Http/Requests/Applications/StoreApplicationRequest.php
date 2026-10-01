<?php

namespace App\Http\Requests\Applications;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Application::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'senior_citizen_id' => [
                'required',
                'integer',
                Rule::exists('senior_citizens', 'id')->where('status', 'VERIFIED'),
                Rule::unique('applications')->where('program_id', $this->integer('program_id')),
            ],
            'program_id' => [
                'required',
                'integer',
                Rule::exists('programs', 'id')->whereIn('status', ['ACTIVE', 'UPCOMING']),
            ],
            'applied_on' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
