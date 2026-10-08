<?php

namespace App\Http\Requests\Sms;

use App\Models\SeniorCitizen;
use App\Models\SmsMessage;
use App\Services\Sms\SmsText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SendSmsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('recipient_number') && $mobile = SmsText::mobile($this->input('recipient_number'))) {
            $this->merge(['recipient_number' => $mobile]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create', SmsMessage::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'recipient_number' => ['required', 'string', 'regex:/^\+639\d{9}$/'],
            'message' => ['required', 'string', 'max:1600'],
            'senior_citizen_id' => ['nullable', 'integer', Rule::exists('senior_citizens', 'id')->where('status', 'VERIFIED')],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->filled('senior_citizen_id') || $validator->errors()->hasAny(['senior_citizen_id', 'recipient_number'])) {
                return;
            }
            $senior = SeniorCitizen::query()->find($this->integer('senior_citizen_id'));
            if ($senior && SmsText::mobile($senior->contact_number) !== $this->input('recipient_number')) {
                $validator->errors()->add('recipient_number', 'The number does not match this senior citizen.');
            }
        }];
    }
}
