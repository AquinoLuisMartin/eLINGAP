<?php

namespace App\Http\Controllers\Payouts;

use App\Enums\PayoutStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payouts\UpdatePayoutStatusRequest;
use App\Models\Payout;
use App\Models\PayoutSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PayoutController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Payout::class);

        return view('payouts.index', ['payouts' => Payout::query()->with(['schedule.program', 'seniorCitizen'])->latest('id')->paginate(15)]);
    }

    public function show(Payout $payout): View
    {
        Gate::authorize('view', $payout);

        return view('payouts.show', ['payout' => $payout->load(['schedule.program', 'seniorCitizen', 'releasedBy'])]);
    }

    public function updateStatus(UpdatePayoutStatusRequest $request, Payout $payout): RedirectResponse
    {
        DB::transaction(function () use ($request, $payout): void {
            $lockedPayout = Payout::query()->lockForUpdate()->findOrFail($payout->id);
            $lockedPayout->update([
                'status' => $request->string('status')->toString(),
                'released_at' => $request->string('status')->toString() === PayoutStatus::Released->value ? now() : null,
                'released_by' => $request->string('status')->toString() === PayoutStatus::Released->value ? $request->user()->id : null,
            ]);
        });

        return back()->with('status', 'Payout status updated.');
    }
}
