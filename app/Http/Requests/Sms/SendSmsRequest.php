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
    private ?string $masterlistNumber = null;

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('recipient_number')) && $mobile = SmsText::mobile($this->input('recipient_number'))) {
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
            'recipient_number' => [Rule::excludeIf($this->filled('program_id')), 'required', 'string', 'regex:/^\+639\d{9}$/'],
            'message' => ['required', 'string', 'max:1600'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'senior_citizen_id' => ['required_with:program_id', 'nullable', 'integer', Rule::exists('senior_citizens', 'id')->where('status', 'VERIFIED')],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->filled('senior_citizen_id') || $validator->errors()->hasAny(['senior_citizen_id', 'recipient_number', 'program_id'])) {
                return;
            }
            if ($this->filled('program_id')) {
                $senior = SeniorCitizen::query()->onProgramMasterlist($this->integer('program_id'))->find($this->integer('senior_citizen_id'));
                $this->masterlistNumber = $senior?->smsContact();
                if ($this->masterlistNumber === null) {
                    $validator->errors()->add('senior_citizen_id', 'Choose a beneficiary with a valid mobile number from the selected program.');
                }

                return;
            }
            $senior = SeniorCitizen::query()->find($this->integer('senior_citizen_id'));
            if ($senior && $senior->smsContact() !== $this->input('recipient_number')) {
                $validator->errors()->add('recipient_number', 'The number does not match this senior citizen.');
            }
        }];
    }

    public function recipientNumber(): string
    {
        return $this->masterlistNumber ?? $this->validated('recipient_number');
    }
}
