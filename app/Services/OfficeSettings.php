<?php

namespace App\Services;

use App\Models\SystemConfiguration;
use Illuminate\Validation\ValidationException;

class OfficeSettings
{
    public function values(): array
    {
        return array_replace(array_fill_keys(SystemConfiguration::OFFICE_KEYS, null), SystemConfiguration::query()
            ->whereIn('key', SystemConfiguration::OFFICE_KEYS)->whereNotNull('value')->where('value', '!=', '')
            ->pluck('value', 'key')->map(fn ($value) => blank(trim($value)) ? null : trim($value))->all());
    }

    public function requireLocality(): array
    {
        $values = $this->values();
        if (blank($values['municipality']) || blank($values['province'])) {
            throw ValidationException::withMessages(['configuration' => 'Configure the municipality and province in System Configuration before recording transfers or printing official documents.']);
        }

        return $values;
    }

    public function requireIdentity(): array
    {
        $values = $this->requireLocality();
        if (blank($values['office_name']) || blank($values['organization_name'])) {
            throw ValidationException::withMessages(['configuration' => 'Configure the office and organization names in System Configuration before printing official documents.']);
        }

        return $values;
    }
}
