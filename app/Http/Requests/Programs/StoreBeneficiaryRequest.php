<?php

namespace App\Http\Requests\Programs;

use App\Models\Beneficiary;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBeneficiaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Beneficiary::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'program_id' => ['required', 'integer', Rule::in([$this->route('program')->id])],
            'senior_citizen_id' => ['required', 'integer', Rule::exists('senior_citizens', 'id')->where('status', 'VERIFIED'), Rule::unique('beneficiaries', 'senior_citizen_id')->where('program_id', $this->route('program')->id)],
            'application_id' => ['nullable', 'integer', Rule::exists('applications', 'id')
                ->where('program_id', $this->route('program')->id)
                ->where('senior_citizen_id', $this->integer('senior_citizen_id'))
                ->where('status', 'APPROVED')],
            'enrolled_on' => ['required', 'date'],
            'status' => ['sometimes', 'string', 'in:ACTIVE,INACTIVE'],
        ];
    }
}
