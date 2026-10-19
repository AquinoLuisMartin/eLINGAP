<?php

namespace App\Services\SeniorCitizens;

use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\SeniorCitizen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SeniorImport
{
    public function prepare(array $source): array
    {
        Gate::authorize('create', SeniorCitizen::class);
        $barangays = Barangay::query()->where('is_active', true)->sharedLock()->get(['id', 'name', 'code']);
        $rows = [];
        foreach ($source as $sourceRow) {
            $data = $sourceRow['values'];
            $matches = $barangays->filter(fn ($barangay) => in_array(mb_strtoupper(trim($data['barangay'] ?? '')), [mb_strtoupper($barangay->name), mb_strtoupper($barangay->code)], true));
            $data['barangay_id'] = $matches->count() === 1 ? $matches->first()->id : null;
            $data['sex'] = match (mb_strtoupper($data['sex'] ?? '')) {
                'M', 'MALE' => 'MALE', 'F', 'FEMALE' => 'FEMALE', 'OTHER' => 'OTHER', default => $data['sex'] ?? '',
            };
            $data['civil_status'] = mb_strtoupper($data['civil_status'] ?? '');
            $date = $data['birth_date'] ?? '';
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})\z/', $date, $parts)) {
                $data['birth_date'] = "{$parts[3]}-{$parts[1]}-{$parts[2]}";
            }
            $data = SeniorRegistrationInput::normalize($data);
            $data['osca_id_number'] = filled($data['osca_id_number'] ?? null) ? mb_strtoupper($data['osca_id_number']) : null;
            $data = self::registrationData($data);
            $validator = Validator::make($data, self::rules($data), [...SeniorRegistrationInput::messages(), 'barangay_id.required' => 'Barangay must match one active barangay name or code.']);
            $rows[] = ['row' => $sourceRow['row'], 'barangay' => $matches->count() === 1 ? $matches->first()->name : ($sourceRow['values']['barangay'] ?? ''), 'data' => $data, 'state' => 'ready', 'errors' => $validator->errors()->all()];
        }
        $valid = collect($rows)->where('errors', [])->pluck('data');
        $existingRecords = $valid->isEmpty() ? collect() : SeniorCitizen::query()
            ->whereIn('birth_date', $valid->pluck('birth_date')->unique())
            ->whereIn(DB::raw('lower(first_name)'), $valid->pluck('first_name')->map(fn ($name) => mb_strtolower($name))->unique())
            ->whereIn(DB::raw('lower(last_name)'), $valid->pluck('last_name')->map(fn ($name) => mb_strtolower($name))->unique())
            ->toBase()->get(['id', 'first_name', 'middle_name', 'last_name', 'name_suffix', 'birth_date', 'osca_id_number'])
            ->keyBy(fn ($record) => $this->identity((array) $record));
        $existingIds = $valid->isEmpty() ? collect() : SeniorCitizen::query()
            ->whereIn(DB::raw('upper(osca_id_number)'), $valid->pluck('osca_id_number')->filter(fn ($id) => $id !== null)->unique())
            ->toBase()->get(['id', 'osca_id_number'])->keyBy(fn ($record) => mb_strtoupper($record->osca_id_number));
        $identities = [];
        $ids = [];
        foreach ($rows as &$row) {
            $data = $row['data'];
            $errors = $row['errors'];
            if (! $errors) {
                $identity = $this->identity($data);
                $existing = $existingRecords->get($identity);
                $idOwner = $data['osca_id_number'] !== null ? $existingIds->get($data['osca_id_number']) : null;
                if (($idOwner && (! $existing || $idOwner->id !== $existing->id)) || ($existing && $data['osca_id_number'] !== null && mb_strtoupper($existing->osca_id_number ?? '') !== $data['osca_id_number'])) {
                    $errors[] = 'The OSCA ID conflicts with an existing record. Check the original records before importing.';
                } elseif (isset($ids[$data['osca_id_number'] ?? '']) && $ids[$data['osca_id_number']] !== $identity) {
                    $errors[] = 'This OSCA ID is used for another senior in this file.';
                } elseif ($existing || array_key_exists($identity, $identities)) {
                    $row['state'] = 'duplicate';
                }
                if (array_key_exists($identity, $identities) && $identities[$identity] !== $data['osca_id_number']) {
                    $errors[] = 'Rows with the same identity have different OSCA IDs. Correct the file before importing.';
                }
                $identities[$identity] = $data['osca_id_number'];
                if ($data['osca_id_number'] !== null) {
                    $ids[$data['osca_id_number']] = $identity;
                }
            }
            $row['errors'] = $errors;
            if ($errors) {
                $row['state'] = 'invalid';
            }
        }
        unset($row);

        return ['rows' => $rows, 'ready' => count(array_filter($rows, fn ($row) => $row['state'] === 'ready')),
            'duplicates' => count(array_filter($rows, fn ($row) => $row['state'] === 'duplicate')),
            'invalid' => count(array_filter($rows, fn ($row) => $row['state'] === 'invalid'))];
    }

    public static function registrationData(array $data): array
    {
        $fields = [...array_diff(SeniorImportReader::COLUMNS, ['barangay']), 'barangay_id'];

        return [...array_intersect_key($data, array_flip($fields)),
            'applicant_type' => filled($data['representative_name'] ?? null) || filled($data['representative_contact'] ?? null) || filled($data['representative_relationship'] ?? null) ? 'REPRESENTATIVE' : 'SELF',
            'requirements_status' => 'REVIEW', 'requirements_checks' => ['identity' => false, 'residency' => false, 'authorization' => false],
            'review_reason' => 'Historical record imported. Check identity, residency, existing OSCA ID and representative authorization before verification.'];
    }

    public static function rules(array $data): array
    {
        $rules = SeniorRegistrationInput::rules($data);
        unset($rules['authorization_file'], $rules['identification_file'], $rules['photo']);
        $rules['barangay_id'] = ['required', 'integer'];
        $rules['osca_id_number'] = ['nullable', 'string', 'max:50', 'regex:/\A[A-Z0-9][A-Z0-9 .\/-]*\z/'];

        return $rules;
    }

    private function identity(array $data): string
    {
        return json_encode(array_map(fn ($field) => mb_strtolower(trim($data[$field] ?? '')), ['first_name', 'middle_name', 'last_name', 'name_suffix', 'birth_date']), JSON_THROW_ON_ERROR);
    }

    public function commit(array $source): array
    {
        Gate::authorize('create', SeniorCitizen::class);

        return DB::transaction(function () use ($source): array {
            SeniorRegistration::lockIdentityChanges();
            $prepared = $this->prepare($source);
            if ($prepared['invalid']) {
                $errors = [];
                foreach ($prepared['rows'] as $row) {
                    foreach ($row['errors'] as $error) {
                        $errors[] = 'Row '.$row['row'].': '.$error;
                    }
                }
                throw ValidationException::withMessages(['import' => $errors]);
            }
            $batchId = (string) Str::uuid();
            $historicalIds = 0;
            foreach ($prepared['rows'] as $row) {
                if ($row['state'] !== 'ready') {
                    continue;
                }
                $data = $row['data'];
                $senior = SeniorCitizen::create([
                    ...collect($data)->except('requirements_status')->all(),
                    'registration_number' => 'SC-'.Str::ulid(), 'status' => 'PENDING',
                ]);
                $historicalIds += (int) filled($senior->osca_id_number);
                $senior->histories()->create(['changed_by' => auth()->id(), 'action' => 'imported', 'changes' => ['batch_id' => $batchId, 'status' => 'PENDING', 'source_row' => $row['row'], 'historical_id_preserved' => filled($senior->osca_id_number)]]);
            }
            if ($prepared['ready'] > 0) {
                AuditLog::create(['user_id' => auth()->id(), 'action' => 'senior_citizen.import_completed', 'new_values' => ['batch_id' => $batchId, 'created' => $prepared['ready'], 'skipped' => $prepared['duplicates'], 'historical_ids_preserved' => $historicalIds], 'ip_address' => request()->ip()]);
            }

            return ['created' => $prepared['ready'], 'skipped' => $prepared['duplicates']];
        });
    }
}
