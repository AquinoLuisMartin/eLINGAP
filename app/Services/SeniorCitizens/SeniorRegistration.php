<?php

namespace App\Services\SeniorCitizens;

use App\Models\SeniorCitizen;
use App\Services\Benefits\BenefitAudit;
use App\Services\Benefits\SeniorEligibilityRecord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class SeniorRegistration
{
    public static function lockIdentityChanges(): void
    {
        // Serialize identity changes and historical ID imports until commit.
        DB::select('select pg_advisory_xact_lock(194701, 1)');
    }

    public function __construct(private SeniorEligibilityRecord $eligibility, private IdentityDocuments $identityDocuments) {}

    public function create(array $input, bool $requireAuthorizationFile = true, bool $requireIdentityDocuments = false): SeniorCitizen
    {
        Gate::authorize('create', SeniorCitizen::class);
        $data = SeniorRegistrationInput::validator(SeniorRegistrationInput::normalize($input), requireAuthorizationFile: $requireAuthorizationFile, requireIdentityDocuments: $requireIdentityDocuments)->validate();
        $paths = [];
        $documents = [];
        try {
            if ($requireIdentityDocuments) {
                $documents['BIRTH_CERTIFICATE'] = $this->storeIdentityDocument($data['birth_certificate_file'], 'birth_certificate_file');
                $documents['IDENTIFICATION'] = $this->storeIdentityDocument($data['identification_file'], 'identification_file');
                $documents['IDENTIFICATION']['id_type_id'] = $data['id_type_id'];
            }

            return DB::transaction(function () use ($data, $documents, &$paths, $requireAuthorizationFile, $requireIdentityDocuments) {
                self::lockIdentityChanges();
                $data = SeniorRegistrationInput::validator($data, requireAuthorizationFile: $requireAuthorizationFile, requireIdentityDocuments: $requireIdentityDocuments)->validate();
                $senior = SeniorCitizen::create([
                    ...collect($data)->except(['photo', 'authorization_file', 'identification_file', 'birth_certificate_file', 'id_type_id', 'requirements_status'])->all(),
                    'status' => 'PENDING',
                ]);
                foreach (['authorization_file' => 'AUTHORIZATION', 'identification_file' => 'IDENTIFICATION'] as $field => $type) {
                    if (! isset($documents[$type]) && ($file = $data[$field] ?? null)) {
                        $path = $file->store('senior-documents', 'local');
                        $paths[] = $path;
                        $senior->documents()->create(['document_type' => $type, 'path' => $path, 'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'size' => $file->getSize(), 'uploaded_by' => auth()->id()]);
                    }
                }
                foreach ($documents as $type => $document) {
                    $senior->documents()->create([...$document, 'document_type' => $type, 'uploaded_by' => auth()->id()]);
                }
                $senior->histories()->create(['changed_by' => auth()->id(), 'action' => 'created', 'changes' => $senior->only(['osca_id_number', 'status'])]);
                BenefitAudit::record('senior_citizen.created', $senior, $senior->only(['osca_id_number', 'status']));

                if ($data['requirements_status'] === 'CHECKED') {
                    $this->eligibility->verify($senior, true, $requireAuthorizationFile);
                } else {
                    $senior->histories()->create(['changed_by' => auth()->id(), 'action' => 'requirements_review_requested', 'changes' => $senior->only(['requirements_checks', 'review_reason'])]);
                    BenefitAudit::record('senior_citizen.requirements_review_requested', $senior);
                }

                return $senior->refresh();
            });
        } catch (Throwable $exception) {
            foreach ($documents as $document) {
                rescue(fn () => $this->identityDocuments->delete($document['storage_disk'], $document['path']));
            }
            Storage::disk('local')->delete($paths);
            throw $exception;
        }
    }

    private function storeIdentityDocument(UploadedFile $file, string $field): array
    {
        try {
            return $this->identityDocuments->store($file);
        } catch (RuntimeException $exception) {
            report($exception);

            throw ValidationException::withMessages([$field => 'The document could not be saved. Please try again.']);
        }
    }
}
