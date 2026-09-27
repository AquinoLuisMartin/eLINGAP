<?php

namespace App\Http\Controllers\Payouts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payouts\StorePayoutScheduleRequest;
use App\Models\PayoutSchedule;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PayoutScheduleController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', PayoutSchedule::class);

        return view('payouts.schedules.index', [
            'schedules' => PayoutSchedule::query()->with('program')->latest('scheduled_on')->paginate(15),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', PayoutSchedule::class);

        return view('payouts.schedules.create', ['programs' => Program::query()->orderBy('name')->get(['id', 'name'])]);
    }

    public function store(StorePayoutScheduleRequest $request): RedirectResponse
    {
        $schedule = PayoutSchedule::create([
            ...$request->validated(),
            'status' => 'SCHEDULED',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('payout-schedules.show', $schedule)->with('status', 'Payout schedule created.');
    }

    public function show(PayoutSchedule $payoutSchedule): View
    {
        Gate::authorize('view', $payoutSchedule);

        return view('payouts.show', ['schedule' => $payoutSchedule->load(['program', 'payouts.seniorCitizen'])]);
    }
}
