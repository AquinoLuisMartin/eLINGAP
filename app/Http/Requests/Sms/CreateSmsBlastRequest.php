<?php

namespace App\Http\Requests\Sms;

use Illuminate\Foundation\Http\FormRequest;

class CreateSmsBlastRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\SmsMessage::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:1600'],
            'status' => ['nullable', 'string', 'in:VERIFIED,ACTIVE'],
        ];
    }
}
