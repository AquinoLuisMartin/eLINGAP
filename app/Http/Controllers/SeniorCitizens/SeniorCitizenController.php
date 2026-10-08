<?php

namespace App\Http\Controllers\SeniorCitizens;

use App\Enums\SeniorCitizenStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SeniorCitizens\StoreSeniorCitizenRequest;
use App\Http\Requests\SeniorCitizens\UpdateSeniorCitizenRequest;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\SeniorCitizen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            'status' => ['nullable', 'in:PENDING,VERIFIED,ARCHIVED,DECEASED'],
            'age' => ['nullable', 'in:60-69,70-79,80+'],
        ]);
        $query = SeniorCitizen::query()->with('barangay')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->searchable($search))
            ->when($filters['barangay_id'] ?? null, fn ($query, $id) => $query->where('barangay_id', $id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['age'] ?? null, function ($query, $age) {
                match ($age) {
                    '60-69' => $query->where('birth_date', '>', today()->subYears(70)),
                    '70-79' => $query->whereBetween('birth_date', [today()->subYears(80)->addDay(), today()->subYears(70)]),
                    '80+' => $query->where('birth_date', '<=', today()->subYears(80)),
                };
            });

        return view('senior-citizens.index', [
            'seniorCitizens' => (clone $query)->latest('id')->paginate(15)->withQueryString(),
            'counts' => [
                'Total registry' => (clone $query)->count(),
                'Age 60–69' => (clone $query)->where('birth_date', '>', today()->subYears(70))->count(),
                'Age 70–79' => (clone $query)->whereBetween('birth_date', [today()->subYears(80)->addDay(), today()->subYears(70)])->count(),
                'Age 80+' => (clone $query)->where('birth_date', '<=', today()->subYears(80))->count(),
            ],
            'barangays' => Barangay::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', SeniorCitizen::class);

        return view('senior-citizens.create', ['barangays' => Barangay::query()->where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(StoreSeniorCitizenRequest $request): RedirectResponse
    {
        $photoPath = null;
        try {
            $seniorCitizen = DB::transaction(function () use ($request, &$photoPath) {
                $seniorCitizen = SeniorCitizen::create([
                    ...$request->safe()->except('photo'),
                    'registration_number' => 'SC-'.Str::ulid(),
                ]);

                if ($photo = $request->file('photo')) {
                    $photoPath = $photo->store('senior-photos');
                    $seniorCitizen->documents()->create(['document_type' => 'PHOTO', 'path' => $photoPath, 'original_name' => $photo->getClientOriginalName(), 'mime_type' => $photo->getMimeType(), 'size' => $photo->getSize(), 'uploaded_by' => $request->user()->id]);
                }

                $seniorCitizen->histories()->create([
                    'changed_by' => $request->user()->id,
                    'action' => 'created',
                    'changes' => $seniorCitizen->only(['registration_number', 'status']),
                ]);
                $this->recordAudit($request, 'senior_citizen.created', $seniorCitizen, null, $seniorCitizen->only(['registration_number', 'status']));

                return $seniorCitizen;
            });
        } catch (Throwable $exception) {
            if ($photoPath) {
                Storage::delete($photoPath);
            }
            throw $exception;
        }

        return redirect()->route('senior-citizens.show', $seniorCitizen)->with('status', 'Senior citizen record created.');
    }

    public function show(SeniorCitizen $seniorCitizen): View
    {
        Gate::authorize('view', $seniorCitizen);

        return view('senior-citizens.show', [
            'seniorCitizen' => $seniorCitizen->load(['barangay', 'histories.changedBy', 'beneficiaries.program']),
            'photo' => $seniorCitizen->documents()->where('document_type', 'PHOTO')->latest('id')->first(),
        ]);
    }

    public function photo(SeniorCitizen $seniorCitizen): BinaryFileResponse
    {
        Gate::authorize('view', $seniorCitizen);
        $photo = $seniorCitizen->documents()->where('document_type', 'PHOTO')->latest('id')->firstOrFail();

        $response = response()->file(Storage::path($photo->path), ['Content-Type' => $photo->mime_type]);
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    public function edit(SeniorCitizen $seniorCitizen): View
    {
        Gate::authorize('update', $seniorCitizen);

        return view('senior-citizens.edit', [
            'seniorCitizen' => $seniorCitizen,
            'barangays' => Barangay::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateSeniorCitizenRequest $request, SeniorCitizen $seniorCitizen): RedirectResponse
    {
        $photoPath = null;
        try {
            DB::transaction(function () use ($request, $seniorCitizen, &$photoPath) {
                $seniorCitizen = SeniorCitizen::query()->lockForUpdate()->findOrFail($seniorCitizen->id);
                abort_if($seniorCitizen->status === SeniorCitizenStatus::Deceased, 409, 'A deceased record cannot be edited.');
                $data = $request->safe()->except('photo');
                $oldValues = $seniorCitizen->only(array_keys($data));
                $seniorCitizen->update($data);
                if ($photo = $request->file('photo')) {
                    $photoPath = $photo->store('senior-photos');
                    $seniorCitizen->documents()->create(['document_type' => 'PHOTO', 'path' => $photoPath, 'original_name' => $photo->getClientOriginalName(), 'mime_type' => $photo->getMimeType(), 'size' => $photo->getSize(), 'uploaded_by' => $request->user()->id]);
                }
                $seniorCitizen->histories()->create([
                    'changed_by' => $request->user()->id,
                    'action' => 'updated',
                    'changes' => ['from' => $oldValues, 'to' => $seniorCitizen->only(array_keys($data)), 'photo_updated' => (bool) $photoPath],
                ]);
                $this->recordAudit($request, 'senior_citizen.updated', $seniorCitizen, $oldValues, $seniorCitizen->only(array_keys($data)));
            });
        } catch (Throwable $exception) {
            if ($photoPath) {
                Storage::delete($photoPath);
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
            abort_if($seniorCitizen->status === SeniorCitizenStatus::Deceased, 409, 'A deceased record cannot be archived.');
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
