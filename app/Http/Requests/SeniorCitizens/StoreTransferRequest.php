<?php

namespace App\Http\Requests\SeniorCitizens;

use App\Services\OfficeSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('transfer', $this->route('senior_citizen')) ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['INTERNAL', 'EXTERNAL'])],
            'requested_by' => ['required', Rule::in(['SELF', 'OTHER'])],
            'requester_name' => ['exclude_unless:requested_by,OTHER', 'required', 'string', 'max:180'],
            'requester_relationship' => ['exclude_unless:requested_by,OTHER', 'required', 'string', 'max:100'],
            'barangay_id' => ['exclude_unless:type,INTERNAL', 'required', 'integer', Rule::exists('barangays', 'id')->where('is_active', true)],
            'destination_municipality' => ['exclude_unless:type,EXTERNAL', 'required', 'string', 'max:100'],
            'destination_province' => ['exclude_unless:type,EXTERNAL', 'required', 'string', 'max:100'],
            'destination_barangay' => ['exclude_unless:type,EXTERNAL', 'required', 'string', 'max:100'],
            'destination_address' => ['required', 'string', 'max:1000'],
            'transferred_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:'.$this->route('senior_citizen')->created_at->toDateString()],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->any()) {
                return;
            }
            $office = app(OfficeSettings::class)->requireLocality();
            if ($this->input('type') === 'EXTERNAL'
                && mb_strtolower(trim((string) $this->input('destination_municipality'))) === mb_strtolower($office['municipality'])
                && mb_strtolower(trim((string) $this->input('destination_province'))) === mb_strtolower($office['province'])) {
                $validator->errors()->add('destination_municipality', 'Use an internal transfer for a destination within the configured municipality and province.');
            }
        }];
    }
}
