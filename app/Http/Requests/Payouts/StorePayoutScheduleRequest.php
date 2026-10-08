<?php

namespace App\Http\Requests\Payouts;

use App\Models\Payout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayoutScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Payout::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'program_id' => ['required', 'integer', Rule::exists('programs', 'id')->whereIn('status', ['ACTIVE', 'UPCOMING'])],
            'reference_number' => ['required', 'string', 'max:40', 'unique:payout_schedules,reference_number'],
            'scheduled_on' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
