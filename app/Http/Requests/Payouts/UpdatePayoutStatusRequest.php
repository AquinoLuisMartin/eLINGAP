<?php

namespace App\Http\Requests\Payouts;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayoutStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('payout')) ?? false;
    }

    public function rules(): array
    {
        return ['status' => ['required', 'string', 'in:PENDING,RELEASED,FAILED']];
    }
}
