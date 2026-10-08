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
        return [
            'status' => ['required', 'in:RELEASED'],
            'claimant_type' => ['required', 'in:SELF,PROXY'],
            'claimant_name' => ['required_if:claimant_type,PROXY', 'nullable', 'string', 'max:200'],
            'claimant_relationship' => ['required_if:claimant_type,PROXY', 'nullable', 'string', 'max:100'],
            'claimant_contact' => ['required_if:claimant_type,PROXY', 'nullable', 'string', 'max:30'],
            'osca_id_checked' => ['accepted'],
            'authorization_checked' => ['required_if:claimant_type,PROXY', 'accepted_if:claimant_type,PROXY'],
            'representative_id_checked' => ['required_if:claimant_type,PROXY', 'accepted_if:claimant_type,PROXY'],
        ];
    }
}
