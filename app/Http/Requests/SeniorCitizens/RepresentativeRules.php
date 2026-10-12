<?php

namespace App\Http\Requests\SeniorCitizens;

use App\Services\SeniorCitizens\SeniorRegistrationInput;

trait RepresentativeRules
{
    protected function normalizeContacts(): void
    {
        $this->merge(SeniorRegistrationInput::normalize($this->only(['contact_number', 'representative_contact'])));
    }

    protected function representativeRules(): array
    {
        return SeniorRegistrationInput::representativeRules($this->all(), $this->route('senior_citizen'));
    }
}
