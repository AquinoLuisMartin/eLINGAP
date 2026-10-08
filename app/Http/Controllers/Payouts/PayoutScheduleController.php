<?php

namespace App\Http\Controllers\Payouts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payouts\StorePayoutScheduleRequest;
use App\Models\Beneficiary;
use App\Models\Payout;
use App\Models\PayoutSchedule;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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
        $schedule = DB::transaction(function () use ($request): PayoutSchedule {
            $schedule = PayoutSchedule::create([
                ...$request->validated(),
                'status' => 'SCHEDULED',
                'created_by' => $request->user()->id,
            ]);
            Beneficiary::query()->where('program_id', $schedule->program_id)->where('status', 'ACTIVE')
                ->whereHas('seniorCitizen', fn ($query) => $query->where('status', 'VERIFIED'))
                ->select(['id', 'senior_citizen_id'])->chunkById(200, function ($beneficiaries) use ($schedule): void {
                    foreach ($beneficiaries as $beneficiary) {
                        Payout::create(['payout_schedule_id' => $schedule->id, 'senior_citizen_id' => $beneficiary->senior_citizen_id, 'amount' => $schedule->amount, 'status' => 'PENDING']);
                    }
                });

            return $schedule;
        });

        return redirect()->route('payout-schedules.show', $schedule)->with('status', 'Payout schedule created.');
    }

    public function show(PayoutSchedule $payoutSchedule): View
    {
        Gate::authorize('view', $payoutSchedule);

        return view('payouts.schedules.show', [
            'schedule' => $payoutSchedule->load('program'),
            'payouts' => $payoutSchedule->payouts()->with('seniorCitizen')->latest('id')->paginate(20),
        ]);
    }
}
