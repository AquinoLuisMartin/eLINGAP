<?php

namespace App\Http\Controllers\Payouts;

use App\Enums\PayoutStatus;
use App\Enums\SeniorCitizenStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payouts\UpdatePayoutStatusRequest;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\Payout;
use App\Models\SeniorCitizen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PayoutController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Payout::class);

        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:PENDING,UNPAID,UNCLAIMED,CLAIMED,RELEASED,FAILED,VOIDED'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'barangay_id' => ['nullable', 'integer', 'exists:barangays,id'],
        ]);

        return view('payouts.index');
    }

    public function show(Payout $payout): View
    {
        Gate::authorize('view', $payout);

        return view('payouts.show', ['payout' => $payout->load(['schedule.program', 'seniorCitizen.barangay', 'releasedBy'])]);
    }

    public function updateStatus(UpdatePayoutStatusRequest $request, Payout $payout): RedirectResponse
    {
        DB::transaction(function () use ($request, $payout): void {
            $lockedPayout = Payout::query()->lockForUpdate()->findOrFail($payout->id);
            abort_unless($lockedPayout->status === PayoutStatus::Pending, 409, 'This payout has already been processed.');
            $senior = SeniorCitizen::query()->lockForUpdate()->findOrFail($lockedPayout->senior_citizen_id);
            abort_unless($senior->status === SeniorCitizenStatus::Verified, 422, 'The senior citizen is not eligible for release.');
            $schedule = $lockedPayout->schedule;
            abort_unless($schedule->program->status === 'ACTIVE', 422, 'The program is not active.');
            $beneficiary = Beneficiary::query()->where('program_id', $schedule->program_id)
                ->where('senior_citizen_id', $senior->id)->lockForUpdate()->first();
            abort_unless($beneficiary?->status === 'ACTIVE', 422, 'The beneficiary is no longer active in this program.');
            $validated = $request->validated();
            $lockedPayout->update([
                'status' => PayoutStatus::Released,
                'released_at' => now(),
                'released_by' => $request->user()->id,
                'claimant_type' => $validated['claimant_type'],
                'claimant_name' => $validated['claimant_type'] === 'PROXY' ? $validated['claimant_name'] : $senior->full_name,
                'claimant_relationship' => $validated['claimant_relationship'] ?? null,
                'claimant_contact' => $validated['claimant_contact'] ?? null,
                'osca_id_checked' => true,
                'authorization_checked' => $validated['claimant_type'] === 'PROXY',
                'representative_id_checked' => $validated['claimant_type'] === 'PROXY',
            ]);
            AuditLog::create(['user_id' => $request->user()->id, 'action' => 'payout.released', 'auditable_type' => Payout::class, 'auditable_id' => $lockedPayout->id, 'new_values' => ['claimant_type' => $validated['claimant_type']], 'ip_address' => $request->ip()]);
        });

        return back()->with('status', 'Payout released.');
    }

    public function reverse(Request $request, Payout $payout): RedirectResponse
    {
        Gate::authorize('reverse', $payout);
        $validated = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);
        DB::transaction(function () use ($request, $payout, $validated): void {
            $locked = Payout::query()->lockForUpdate()->findOrFail($payout->id);
            abort_unless($locked->status === PayoutStatus::Released, 409, 'Only a released payout can be reversed.');
            $locked->update(['status' => PayoutStatus::Voided, 'reversal_reason' => $validated['reason'], 'reversed_at' => now(), 'reversed_by' => $request->user()->id]);
            AuditLog::create(['user_id' => $request->user()->id, 'action' => 'payout.reversed', 'auditable_type' => Payout::class, 'auditable_id' => $locked->id, 'new_values' => ['reason' => $validated['reason']], 'ip_address' => $request->ip()]);
        });

        return back()->with('status', 'Payout reversed.');
    }
}
