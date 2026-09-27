<?php

namespace App\Http\Requests\Sms;

use Illuminate\Foundation\Http\FormRequest;

class StoreSmsTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\SmsMessage::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', 'unique:sms_templates,name'],
            'body' => ['required', 'string', 'max:1600'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
