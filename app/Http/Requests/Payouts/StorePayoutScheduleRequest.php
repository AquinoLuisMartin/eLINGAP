<?php

namespace App\Http\Requests\Payouts;

use Illuminate\Foundation\Http\FormRequest;

class StorePayoutScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Payout::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'reference_number' => ['required', 'string', 'max:40', 'unique:payout_schedules,reference_number'],
            'scheduled_on' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
