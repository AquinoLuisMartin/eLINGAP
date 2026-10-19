<?php

namespace App\Http\Requests\SeniorCitizens;

use App\Services\SeniorCitizens\SeniorRegistrationInput;

trait RepresentativeRules
{
    protected function normalizeRegistrationInput(): void
    {
        $this->merge(SeniorRegistrationInput::normalize($this->all()));
    }

    protected function representativeRules(): array
    {
        return SeniorRegistrationInput::representativeRules($this->all(), $this->route('senior_citizen'));
    }
}
