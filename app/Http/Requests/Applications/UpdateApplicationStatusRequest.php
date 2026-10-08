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
            'remarks' => ['required_if:status,REJECTED', 'nullable', 'string', 'max:5000'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            if ($this->route('application')?->status?->value !== 'PENDING') {
                $validator->errors()->add('status', 'Only a pending application can be reviewed.');
            }
        }];
    }
}
