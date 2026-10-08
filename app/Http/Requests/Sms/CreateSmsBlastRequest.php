<?php

namespace App\Http\Requests\Sms;

use App\Models\SmsMessage;
use Illuminate\Foundation\Http\FormRequest;

class CreateSmsBlastRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SmsMessage::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:1600'],
            'barangay_id' => ['nullable', 'integer', 'exists:barangays,id'],
            'confirmed' => ['accepted'],
        ];
    }
}
