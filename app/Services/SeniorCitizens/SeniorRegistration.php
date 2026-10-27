<?php

namespace App\Services\SeniorCitizens;

use App\Models\SeniorCitizen;
use App\Services\Benefits\BenefitAudit;
use App\Services\Benefits\SeniorEligibilityRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SeniorRegistration
{
    public static function lockIdentityChanges(): void
    {
        // Serialize identity changes and historical ID imports until commit.
        DB::select('select pg_advisory_xact_lock(194701, 1)');
    }

    public function __construct(private SeniorEligibilityRecord $eligibility) {}

    public function create(array $input): SeniorCitizen
    {
        Gate::authorize('create', SeniorCitizen::class);
        $data = SeniorRegistrationInput::validator(SeniorRegistrationInput::normalize($input))->validate();
        $paths = [];
        try {
            return DB::transaction(function () use ($data, &$paths) {
                self::lockIdentityChanges();
                $data = SeniorRegistrationInput::validator($data)->validate();
                $senior = SeniorCitizen::create([
                    ...collect($data)->except(['photo', 'authorization_file', 'identification_file', 'requirements_status'])->all(),
                    'status' => 'PENDING',
                ]);
                foreach (['authorization_file' => 'AUTHORIZATION', 'identification_file' => 'IDENTIFICATION'] as $field => $type) {
                    if ($file = $data[$field] ?? null) {
                        $path = $file->store('senior-documents', 'local');
                        $paths[] = $path;
                        $senior->documents()->create(['document_type' => $type, 'path' => $path, 'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'size' => $file->getSize(), 'uploaded_by' => auth()->id()]);
                    }
                }
                $senior->histories()->create(['changed_by' => auth()->id(), 'action' => 'created', 'changes' => $senior->only(['osca_id_number', 'status'])]);
                BenefitAudit::record('senior_citizen.created', $senior, $senior->only(['osca_id_number', 'status']));

                if ($data['requirements_status'] === 'CHECKED') {
                    $this->eligibility->verify($senior, true);
                } else {
                    $senior->histories()->create(['changed_by' => auth()->id(), 'action' => 'requirements_review_requested', 'changes' => $senior->only(['requirements_checks', 'review_reason'])]);
                    BenefitAudit::record('senior_citizen.requirements_review_requested', $senior);
                }

                return $senior->refresh();
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }
    }
}
