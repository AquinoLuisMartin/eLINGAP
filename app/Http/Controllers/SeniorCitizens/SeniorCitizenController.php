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
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', SeniorCitizen::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'barangay_id' => ['nullable', 'integer', 'exists:barangays,id'],
            'status' => ['nullable', 'in:PENDING,VERIFIED,ARCHIVED,DECEASED,TRANSFERRED'],
            'age' => ['nullable', 'in:60-69,70-79,80+'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'benefit' => ['nullable', 'in:PENSIONER,NATIONAL_PENSION,LOCAL_PENSION,NONE'],
            'sort' => ['nullable', 'in:id,last_name,first_name,middle_name,benefits'],
            'direction' => ['nullable', 'in:asc,desc'],
        ]);
        $query = SeniorCitizen::query()->with(['barangay', 'activeAssignments.program'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->searchable($search))
            ->when($filters['barangay_id'] ?? null, fn ($query, $id) => $query->where('barangay_id', $id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['age'] ?? null, function ($query, $age) {
                match ($age) {
                    '60-69' => $query->where('birth_date', '>', today()->subYears(70)),
                    '70-79' => $query->whereBetween('birth_date', [today()->subYears(80)->addDay(), today()->subYears(70)]),
                    '80+' => $query->where('birth_date', '<=', today()->subYears(80)),
                };
            })
            ->when($filters['program_id'] ?? null, fn ($query, $programId) => $query->whereHas('activeAssignments', fn ($assignments) => $assignments->where('program_id', $programId)))
            ->when($filters['benefit'] ?? null, function ($query, $benefit) {
                match ($benefit) {
                    'PENSIONER' => $query->where(fn ($pensions) => $pensions->where('has_national_pension', true)->orWhere('has_local_pension', true)),
                    'NATIONAL_PENSION' => $query->where('has_national_pension', true),
                    'LOCAL_PENSION' => $query->where('has_local_pension', true),
                    'NONE' => $query->whereDoesntHave('activeAssignments')->where('has_national_pension', false)->where('has_local_pension', false),
                };
            });
        $sort = $filters['sort'] ?? 'id';
        $direction = $filters['direction'] ?? ($sort === 'id' ? 'desc' : 'asc');
        if ($sort === 'benefits') {
            $query->orderBy(Program::query()->select('name')->whereHas('beneficiaries', fn ($assignments) => $assignments->whereColumn('senior_citizen_id', 'senior_citizens.id')->where('status', 'ACTIVE'))->orderBy('name')->limit(1), $direction)
                ->orderBy('has_national_pension', $direction)->orderBy('has_local_pension', $direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        return view('senior-citizens.index', [
            'seniorCitizens' => (clone $query)->orderBy('id')->paginate(15)->withQueryString(),
            'counts' => [
                'Total registry' => (clone $query)->count(),
                'Age 60–69' => (clone $query)->where('birth_date', '>', today()->subYears(70))->count(),
                'Age 70–79' => (clone $query)->whereBetween('birth_date', [today()->subYears(80)->addDay(), today()->subYears(70)])->count(),
                'Age 80+' => (clone $query)->where('birth_date', '<=', today()->subYears(80))->count(),
            ],
            'barangays' => Barangay::query()->orderBy('name')->get(['id', 'name']),
            'programs' => Program::query()->orderBy('name')->get(['id', 'name']),
        ]);
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
                $oldValues = $seniorCitizen->only(array_keys($data));
                $seniorCitizen->update($data);
                if ($proof = $request->file('authorization_file')) {
                    $authorizationPath = $proof->store('senior-documents', 'local');
                    $seniorCitizen->documents()->create(['document_type' => 'AUTHORIZATION', 'path' => $authorizationPath, 'original_name' => $proof->getClientOriginalName(), 'mime_type' => $proof->getMimeType(), 'size' => $proof->getSize(), 'uploaded_by' => $request->user()->id]);
                }
                $seniorCitizen->histories()->create([
                    'changed_by' => $request->user()->id,
                    'action' => 'updated',
                    'changes' => ['from' => $oldValues, 'to' => $seniorCitizen->only(array_keys($data))],
                ]);
                $this->recordAudit($request, 'senior_citizen.updated', $seniorCitizen, $oldValues, $seniorCitizen->only(array_keys($data)));
            });
        } catch (Throwable $exception) {
            if ($authorizationPath) {
                Storage::disk('local')->delete($authorizationPath);
            }
            throw $exception;
        }

        return redirect()->route('senior-citizens.show', $seniorCitizen)->with('status', 'Senior citizen record updated.');
    }

    public function destroy(Request $request, SeniorCitizen $seniorCitizen): RedirectResponse
    {
        Gate::authorize('delete', $seniorCitizen);
        DB::transaction(function () use ($request, $seniorCitizen): void {
            $seniorCitizen = SeniorCitizen::query()->lockForUpdate()->findOrFail($seniorCitizen->id);
            abort_if(in_array($seniorCitizen->status, [SeniorCitizenStatus::Deceased, SeniorCitizenStatus::Transferred], true), 409, 'A deceased or transferred record cannot be archived.');
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
            'user_agent' => $request->userAgent(),
        ]);
    }
}
