<?php

namespace App\Http\Requests\SeniorCitizens;

use App\Models\SeniorCitizen;
use Illuminate\Foundation\Http\FormRequest;

class PreviewSeniorImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SeniorCitizen::class) ?? false;
    }

    public function rules(): array
    {
        return ['file' => ['required', 'file', 'max:5120', 'extensions:csv,xlsx,xls', 'mimes:csv,txt,xlsx,xls']];
    }
}
