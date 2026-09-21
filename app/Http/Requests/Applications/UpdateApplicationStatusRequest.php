<?php

namespace App\Http\Requests\Applications;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('review', $this->route('application')) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:APPROVED,REJECTED,CANCELLED'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
