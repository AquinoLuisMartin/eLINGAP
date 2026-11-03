<?php

namespace App\Http\Controllers\SeniorCitizens;

use App\Enums\SeniorCitizenStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SeniorCitizens\StoreSeniorCitizenRequest;
use App\Http\Requests\SeniorCitizens\UpdateSeniorCitizenRequest;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Services\SeniorCitizens\IdCardLayout;
use App\Services\SeniorCitizens\RegistrySearch;
use App\Services\SeniorCitizens\SeniorRegistration;
use App\Services\SeniorCitizens\SeniorRegistrationInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class SeniorCitizenController extends Controller
{
    public function index(Request $request, RegistrySearch $search): View
    {
        Gate::authorize('viewAny', SeniorCitizen::class);

        return view('senior-citizens.index', $search->data($request->validate(RegistrySearch::rules()), max(1, $request->integer('page', 1))));
    }

    public function create(): View
    {
        Gate::authorize('create', SeniorCitizen::class);

        return view('senior-citizens.create', ['barangays' => Barangay::query()->where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(StoreSeniorCitizenRequest $request, SeniorRegistration $registration): RedirectResponse
    {
        $seniorCitizen = $registration->create($request->validated());

        return redirect()->route($seniorCitizen->verified_at ? 'senior-id.show' : 'senior-citizens.show', $seniorCitizen)->with('status', $seniorCitizen->verified_at ? 'Requirements checked and registration verified.' : 'Registration saved for requirements review.');
    }

    public function show(SeniorCitizen $seniorCitizen): View
    {
        Gate::authorize('view', $seniorCitizen);

        $seniorCitizen->loadMissing('milestoneClaims:id,senior_citizen_id,milestone_age');

        return view('senior-citizens.show', [
            'seniorCitizen' => $seniorCitizen->load(['barangay', 'histories.changedBy', 'beneficiaries.program', 'transfers.recorder']),
            'photo' => $seniorCitizen->documents()->where('document_type', 'PHOTO')->latest('id')->first(),
            'documents' => $seniorCitizen->documents()->where('document_type', '!=', 'PHOTO')->latest('id')->get(['id', 'document_type', 'created_at']),
            'milestoneAges' => Program::query()->whereNotNull('benefit_code')->get(['benefit_rules'])->flatMap(fn ($program) => array_keys($program->benefit_rules['milestones'] ?? []))->unique()->sort()->values(),
            'barangays' => Barangay::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function photo(SeniorCitizen $seniorCitizen): BinaryFileResponse
    {
        Gate::authorize('view', $seniorCitizen);
        $photo = $seniorCitizen->documents()->where('document_type', 'PHOTO')->latest('id')->firstOrFail();

        $response = response()->file(Storage::disk('local')->path($photo->path), ['Content-Type' => $photo->mime_type]);
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    public function idCard(SeniorCitizen $seniorCitizen, IdCardLayout $layout): View
    {
        Gate::authorize('manageId', $seniorCitizen);
        abort_unless($seniorCitizen->status === SeniorCitizenStatus::Verified && $seniorCitizen->id_generated_at && $seniorCitizen->osca_id_number, 422, 'Verify the senior and record ID issuance before printing.');

        return view('senior-citizens.ids.preview', ['card' => $layout->data($seniorCitizen)]);
    }

    public function edit(SeniorCitizen $seniorCitizen): View
    {
        Gate::authorize('update', $seniorCitizen);

        return view('senior-citizens.edit', [
            'seniorCitizen' => $seniorCitizen,
            'barangays' => Barangay::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function document(SeniorCitizen $seniorCitizen, int $document): BinaryFileResponse
    {
        Gate::authorize('view', $seniorCitizen);
        $file = $seniorCitizen->documents()->findOrFail($document);
        $extension = match ($file->mime_type) {
            'application/pdf' => 'pdf', 'image/png' => 'png', 'image/webp' => 'webp', default => 'jpg',
        };
        $response = response()->download(Storage::disk('local')->path($file->path), 'supporting-document.'.$extension, ['Content-Type' => $file->mime_type]);
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    public function update(UpdateSeniorCitizenRequest $request, SeniorCitizen $seniorCitizen): RedirectResponse
    {
        $authorizationPath = null;
        try {
            DB::transaction(function () use ($request, $seniorCitizen, &$authorizationPath) {
                SeniorRegistration::lockIdentityChanges();
                $seniorCitizen = SeniorCitizen::query()->lockForUpdate()->findOrFail($seniorCitizen->id);
                abort_if(in_array($seniorCitizen->status, [SeniorCitizenStatus::Deceased, SeniorCitizenStatus::Transferred], true), 409, 'A deceased or transferred record cannot be edited.');
                $data = $request->safe()->except(['photo', 'authorization_file']);
                $validator = validator($data, []);
                SeniorRegistrationInput::checkDuplicate($validator, $data, $seniorCitizen);
                $validator->validate();
                $before = $seniorCitizen->only(array_keys($data));
                $seniorCitizen->fill($data);
                $changedFields = array_keys($seniorCitizen->getDirty());
                if (! $changedFields && ! $request->hasFile('authorization_file')) {
                    return;
                }
                $oldValues = array_intersect_key($before, array_flip($changedFields));
                $seniorCitizen->save();
                if ($proof = $request->file('authorization_file')) {
                    $authorizationPath = $proof->store('senior-documents', 'local');
                    $seniorCitizen->documents()->create(['document_type' => 'AUTHORIZATION', 'path' => $authorizationPath, 'original_name' => $proof->getClientOriginalName(), 'mime_type' => $proof->getMimeType(), 'size' => $proof->getSize(), 'uploaded_by' => $request->user()->id]);
                }
                $newValues = $seniorCitizen->only($changedFields);
                if ($authorizationPath) {
                    $newValues['authorization_document_added'] = true;
                }
                $seniorCitizen->histories()->create([
                    'changed_by' => $request->user()->id,
                    'action' => 'updated',
                    'changes' => ['from' => $oldValues, 'to' => $newValues],
                ]);
                $this->recordAudit($request, 'senior_citizen.updated', $seniorCitizen, $oldValues, $newValues);
            });
        } catch (Throwable $exception) {
            if ($authorizationPath) {
                Storage::disk('local')->delete($authorizationPath);
            }
            throw $exception;
        }

        return redirect()->route('senior-citizens.show', [$seniorCitizen, ...collect($request->query())->only(['from', 'search', 'search_by', 'stage', 'barangay_id', 'status', 'age', 'program_id', 'benefit', 'sort', 'direction', 'page'])->all()])->with('status', 'Senior citizen record updated.');
    }

    public function destroy(Request $request, SeniorCitizen $seniorCitizen): RedirectResponse
    {
        Gate::authorize('delete', $seniorCitizen);
        DB::transaction(function () use ($request, $seniorCitizen): void {
            $seniorCitizen = SeniorCitizen::query()->lockForUpdate()->findOrFail($seniorCitizen->id);
            abort_if(in_array($seniorCitizen->status, [SeniorCitizenStatus::Deceased, SeniorCitizenStatus::Transferred], true), 409, 'A deceased or transferred record cannot be archived.');
            if ($seniorCitizen->status === SeniorCitizenStatus::Archived) {
                return;
            }
            $old = $seniorCitizen->status->value;
            $seniorCitizen->update(['status' => 'ARCHIVED']);
            $seniorCitizen->histories()->create(['changed_by' => $request->user()->id, 'action' => 'archived', 'changes' => ['from' => $old]]);
            AuditLog::create(['user_id' => $request->user()->id, 'action' => 'senior_citizen.archived', 'auditable_type' => SeniorCitizen::class, 'auditable_id' => $seniorCitizen->id, 'old_values' => ['status' => $old], 'new_values' => ['status' => 'ARCHIVED'], 'ip_address' => $request->ip()]);
        });

        return redirect()->route('senior-citizens.index')->with('status', 'Senior citizen record archived.');
    }

    private function recordAudit(StoreSeniorCitizenRequest|UpdateSeniorCitizenRequest $request, string $action, SeniorCitizen $seniorCitizen, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'auditable_type' => SeniorCitizen::class,
            'auditable_id' => $seniorCitizen->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr($request->userAgent() ?? '', 0, 1000),
        ]);
    }
}
