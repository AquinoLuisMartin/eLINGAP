<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ReferenceData
{
    private const COLUMNS = [
        'roles' => ['name', 'description'],
        'barangays' => ['name', 'code', 'is_active'],
        'programs' => ['name', 'agency', 'budget', 'starts_on', 'ends_on', 'status', 'description', 'benefit_code', 'government_level', 'benefit_rules', 'default_amount', 'provisional'],
        'sms_templates' => ['name', 'body', 'is_active'],
        'system_configurations' => ['key', 'value', 'type'],
    ];

    public function snapshot(): array
    {
        $data = [];
        foreach (self::COLUMNS as $table => $columns) {
            $query = DB::table($table)->select($columns)->orderBy('id');
            if ($table === 'system_configurations') {
                $query->whereIn('key', ['municipality', 'province']);
            }
            $data[$table] = $query->get()->map(fn ($row) => (array) $row)->all();
        }

        return $data;
    }

    public function apply(array $data, bool $commit = false): array
    {
        $rules = ['data' => ['required', 'array:'.implode(',', array_keys(self::COLUMNS))]];
        foreach (self::COLUMNS as $table => $columns) {
            $rules['data.'.$table] = ['present', 'array'];
            $rules['data.'.$table.'.*'] = ['required', 'array:'.implode(',', $columns)];
        }
        $rules += [
            'data.roles.*.name' => ['required', Rule::in(['ADMIN', 'OSCA_STAFF'])],
            'data.barangays.*.name' => ['required', 'string', 'max:100'],
            'data.barangays.*.code' => ['required', 'string', 'max:20'],
            'data.programs.*.name' => ['required', 'string', 'max:180'],
            'data.programs.*.agency' => ['required', 'string', 'max:180'],
            'data.programs.*.benefit_code' => ['nullable', 'string', 'max:60'],
            'data.programs.*.benefit_rules' => ['nullable', 'json'],
            'data.sms_templates.*.name' => ['required', 'string', 'max:120'],
            'data.sms_templates.*.body' => ['required', 'string'],
            'data.system_configurations.*.key' => ['required', Rule::in(['municipality', 'province'])],
            'data.system_configurations.*.value' => ['required', 'string', 'max:180'],
        ];
        Validator::make(['data' => $data], $rules)->validate();

        return DB::transaction(function () use ($data, $commit): array {
            $summary = [];
            foreach (self::COLUMNS as $table => $columns) {
                $summary[$table] = ['missing' => 0, 'existing' => 0];
                foreach ($data[$table] as $row) {
                    $query = DB::table($table);
                    match ($table) {
                        'barangays' => $query->where(fn ($query) => $query->where('code', $row['code'])->orWhere('name', $row['name'])),
                        'programs' => $query->where(function ($query) use ($row): void {
                            $query->where(fn ($query) => $query->where('name', $row['name'])->where('agency', $row['agency']));
                            if (filled($row['benefit_code'] ?? null)) {
                                $query->orWhere('benefit_code', $row['benefit_code']);
                            }
                        }),
                        'system_configurations' => $query->where('key', $row['key']),
                        default => $query->where('name', $row['name']),
                    };
                    $exists = $query->exists();
                    $summary[$table][$exists ? 'existing' : 'missing']++;
                    if ($commit && ! $exists) {
                        DB::table($table)->insert([...$row, 'created_at' => now(), 'updated_at' => now()]);
                    }
                }
            }

            return $summary;
        });
    }
}
