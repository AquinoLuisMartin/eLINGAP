<?php

namespace App\Http\Requests\Sms;

use Illuminate\Foundation\Http\FormRequest;

class SendSmsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\SmsMessage::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'recipient_number' => ['required', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:1600'],
            'senior_citizen_id' => ['nullable', 'integer', 'exists:senior_citizens,id'],
        ];
    }
}
