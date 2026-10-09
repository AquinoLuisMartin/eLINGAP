<?php

namespace App\Http\Controllers\SeniorCitizens;

use App\Enums\SeniorCitizenStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SeniorCitizens\DeclareDeceasedRequest;
use App\Models\AuditLog;
use App\Models\SeniorCitizen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SeniorCitizenDeathController extends Controller
{
    public function store(DeclareDeceasedRequest $request, SeniorCitizen $seniorCitizen): RedirectResponse
    {
        $validated = $request->validated();
        $path = null;
        try {
            DB::transaction(function () use ($request, $seniorCitizen, $validated, &$path): void {
                $senior = SeniorCitizen::query()->lockForUpdate()->findOrFail($seniorCitizen->id);
                abort_if($senior->status === SeniorCitizenStatus::Deceased, 409, 'Death has already been declared.');
                $document = $request->file('death_document');
                $path = $document->store('senior-documents', 'local');
                $oldStatus = $senior->status->value;
                $senior->update(['status' => SeniorCitizenStatus::Deceased, 'died_on' => $validated['died_on'], 'death_declared_at' => now(), 'death_declared_by' => $request->user()->id]);
                $senior->documents()->create(['document_type' => 'DEATH_CERTIFICATE', 'path' => $path, 'original_name' => $document->getClientOriginalName(), 'mime_type' => $document->getMimeType(), 'size' => $document->getSize(), 'uploaded_by' => $request->user()->id]);
                $senior->histories()->create(['changed_by' => $request->user()->id, 'action' => 'deceased_declared', 'changes' => ['from' => $oldStatus, 'died_on' => $validated['died_on']]]);
                AuditLog::create(['user_id' => $request->user()->id, 'action' => 'senior_citizen.deceased_declared', 'auditable_type' => SeniorCitizen::class, 'auditable_id' => $senior->id, 'old_values' => ['status' => $oldStatus], 'new_values' => ['status' => SeniorCitizenStatus::Deceased->value, 'died_on' => $validated['died_on']], 'ip_address' => $request->ip()]);
            });
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return back()->with('status', 'Death declaration recorded.');
    }

    public function correct(Request $request, SeniorCitizen $seniorCitizen): RedirectResponse
    {
        Gate::authorize('correctDeath', $seniorCitizen);
        $validated = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        DB::transaction(function () use ($request, $seniorCitizen, $validated): void {
            $senior = SeniorCitizen::query()->lockForUpdate()->findOrFail($seniorCitizen->id);
            abort_unless($senior->status === SeniorCitizenStatus::Deceased, 409, 'This record has no death declaration.');
            $senior->update(['status' => SeniorCitizenStatus::Pending, 'died_on' => null, 'death_declared_at' => null, 'death_declared_by' => null, 'verified_at' => null, 'verified_by' => null]);
            $senior->histories()->create(['changed_by' => $request->user()->id, 'action' => 'deceased_corrected', 'changes' => ['reason' => $validated['reason']]]);
            AuditLog::create(['user_id' => $request->user()->id, 'action' => 'senior_citizen.deceased_corrected', 'auditable_type' => SeniorCitizen::class, 'auditable_id' => $senior->id, 'new_values' => ['status' => SeniorCitizenStatus::Pending->value, 'reason' => $validated['reason']], 'ip_address' => $request->ip()]);
        });

        return back()->with('status', 'Death declaration corrected; record requires verification.');
    }
}
