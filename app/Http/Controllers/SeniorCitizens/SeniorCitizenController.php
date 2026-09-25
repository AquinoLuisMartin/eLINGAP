<?php

namespace App\Http\Controllers\SeniorCitizens;

use App\Http\Controllers\Controller;
use App\Http\Requests\SeniorCitizens\StoreSeniorCitizenRequest;
use App\Http\Requests\SeniorCitizens\UpdateSeniorCitizenRequest;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\SeniorCitizen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SeniorCitizenController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', SeniorCitizen::class);

        return view('senior-citizens.index', [
            'seniorCitizens' => SeniorCitizen::query()
                ->with('barangay')
                ->latest('id')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', SeniorCitizen::class);

        return view('senior-citizens.create', ['barangays' => Barangay::query()->where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(StoreSeniorCitizenRequest $request): RedirectResponse
    {
        $seniorCitizen = DB::transaction(function () use ($request) {
            $seniorCitizen = SeniorCitizen::create([
                ...$request->validated(),
                'registration_number' => 'SC-'.now()->format('YmdHis').'-'.random_int(100, 999),
            ]);

            $seniorCitizen->histories()->create([
                'changed_by' => $request->user()->id,
                'action' => 'created',
                'changes' => $seniorCitizen->only(['registration_number', 'status']),
            ]);
            $this->recordAudit($request, 'senior_citizen.created', $seniorCitizen, null, $seniorCitizen->only(['registration_number', 'status']));

            return $seniorCitizen;
        });

        return redirect()->route('senior-citizens.show', $seniorCitizen)->with('status', 'Senior citizen record created.');
    }

    public function show(SeniorCitizen $seniorCitizen): View
    {
        Gate::authorize('view', $seniorCitizen);

        return view('senior-citizens.show', ['seniorCitizen' => $seniorCitizen->load(['barangay', 'histories.changedBy'])]);
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
        $oldValues = $seniorCitizen->only(array_keys($request->validated()));

        DB::transaction(function () use ($request, $seniorCitizen, $oldValues) {
            $seniorCitizen->update($request->validated());
            $seniorCitizen->histories()->create([
                'changed_by' => $request->user()->id,
                'action' => 'updated',
                'changes' => ['from' => $oldValues, 'to' => $seniorCitizen->only(array_keys($request->validated()))],
            ]);
            $this->recordAudit($request, 'senior_citizen.updated', $seniorCitizen, $oldValues, $seniorCitizen->only(array_keys($request->validated())));
        });

        return redirect()->route('senior-citizens.show', $seniorCitizen)->with('status', 'Senior citizen record updated.');
    }

    public function destroy(SeniorCitizen $seniorCitizen): RedirectResponse
    {
        Gate::authorize('delete', $seniorCitizen);
        $seniorCitizen->update(['status' => 'ARCHIVED']);

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
