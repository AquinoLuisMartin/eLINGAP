<?php

namespace App\Services\SeniorCitizens;

use App\Models\SeniorCitizen;
use App\Services\Benefits\BenefitAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class SeniorRegistration
{
    public function create(array $input): SeniorCitizen
    {
        Gate::authorize('create', SeniorCitizen::class);
        $data = SeniorRegistrationInput::validator(SeniorRegistrationInput::normalize($input))->validate();
        $paths = [];
        try {
            return DB::transaction(function () use ($data, &$paths) {
                $senior = SeniorCitizen::create([
                    ...collect($data)->except(['photo', 'authorization_file', 'identification_file'])->all(),
                    'registration_number' => 'SC-'.Str::ulid(), 'status' => 'PENDING',
                ]);
                foreach (['photo' => 'PHOTO', 'authorization_file' => 'AUTHORIZATION', 'identification_file' => 'IDENTIFICATION'] as $field => $type) {
                    if ($file = $data[$field] ?? null) {
                        $path = $file->store($type === 'PHOTO' ? 'senior-photos' : 'senior-documents', 'local');
                        $paths[] = $path;
                        $senior->documents()->create(['document_type' => $type, 'path' => $path, 'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'size' => $file->getSize(), 'uploaded_by' => auth()->id()]);
                    }
                }
                $senior->histories()->create(['changed_by' => auth()->id(), 'action' => 'created', 'changes' => $senior->only(['registration_number', 'status'])]);
                BenefitAudit::record('senior_citizen.created', $senior, $senior->only(['registration_number', 'status']));

                return $senior;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }
    }
}
