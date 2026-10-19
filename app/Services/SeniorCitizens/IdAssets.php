<?php

namespace App\Services\SeniorCitizens;

use App\Enums\SeniorCitizenStatus;
use App\Models\SeniorCitizen;
use App\Models\SeniorCitizenDocument;
use App\Services\Benefits\BenefitAudit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class IdAssets
{
    public function municipalSealDataUri(?string $path): string
    {
        return $this->pathDataUri($path)
            ?? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('images/lgu-seal.png')));
    }

    public function latest(SeniorCitizen $senior, string $type): ?SeniorCitizenDocument
    {
        return $senior->documents()->where('document_type', $type)->latest('id')->first();
    }

    public function save(SeniorCitizen $senior, UploadedFile $file, string $type, ?UploadedFile $original = null): void
    {
        Gate::authorize('manageId', $senior);
        abort_unless(in_array($type, ['PHOTO', 'SIGNATURE']), 422);
        Validator::make(['image' => $file, 'original' => $original], [
            'image' => ['required', 'image', 'mimes:jpeg,png', 'max:2048', 'dimensions:max_width=6000,max_height=6000', ...($type === 'PHOTO' ? ['dimensions:ratio=1/1'] : [])],
            'original' => ['nullable', 'image', 'mimes:jpeg,png', 'max:2048', 'dimensions:max_width=6000,max_height=6000'],
        ], ['image.max' => 'The image must not be greater than 2MB.'])->validate();
        $paths = [];
        try {
            DB::transaction(function () use ($senior, $file, $type, $original, &$paths): void {
                $senior = SeniorCitizen::query()->lockForUpdate()->findOrFail($senior->id);
                $this->assertEditable($senior);
                foreach ([$type => $file, $type.'_ORIGINAL' => $original] as $documentType => $upload) {
                    if (! $upload) {
                        continue;
                    }
                    $path = $upload->store('senior-id-assets', 'local');
                    $paths[] = $path;
                    $senior->documents()->create(['document_type' => $documentType, 'path' => $path, 'original_name' => $upload->getClientOriginalName(), 'mime_type' => $upload->getMimeType(), 'size' => $upload->getSize(), 'uploaded_by' => auth()->id()]);
                }
                BenefitAudit::record('senior_citizen.id_asset_updated', $senior, ['asset' => $type]);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }
    }

    public function useWetSignature(SeniorCitizen $senior): void
    {
        Gate::authorize('manageId', $senior);
        DB::transaction(function () use ($senior): void {
            $senior = SeniorCitizen::query()->lockForUpdate()->findOrFail($senior->id);
            $this->assertEditable($senior);
            // Keep prior signatures privately while excluding them from future prints.
            if ($senior->documents()->where('document_type', 'SIGNATURE')->update(['document_type' => 'SIGNATURE_HISTORY']) > 0) {
                BenefitAudit::record('senior_citizen.wet_signature_selected', $senior);
            }
        });
    }

    public function dataUri(?SeniorCitizenDocument $document): ?string
    {
        return $document ? $this->pathDataUri($document->path) : null;
    }

    private function assertEditable(SeniorCitizen $senior): void
    {
        $allowed = in_array($senior->status, [SeniorCitizenStatus::Pending, SeniorCitizenStatus::Verified], true)
            || ($senior->status === SeniorCitizenStatus::Archived && $senior->verified_at && $senior->id_generated_at);
        abort_unless($allowed && ! $senior->died_on && ! $senior->death_declared_at, 422, 'ID assets cannot be edited for this record.');
    }

    public function pathDataUri(?string $path): ?string
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }
        $mime = Storage::disk('local')->mimeType($path);
        if (! in_array($mime, ['image/jpeg', 'image/png'], true)) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('local')->get($path));
    }
}
