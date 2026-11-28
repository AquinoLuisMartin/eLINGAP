<?php

namespace App\Http\Requests\Sms;

use App\Models\SmsMessage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSmsTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SmsMessage::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('sms_templates', 'name')->withoutTrashed()],
            'body' => ['required', 'string', 'max:1600'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
