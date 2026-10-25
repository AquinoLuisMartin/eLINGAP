<?php

namespace App\Http\Controllers\Payouts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payouts\StorePayoutScheduleRequest;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\BenefitSchedule;
use App\Models\Payout;
use App\Models\PayoutSchedule;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PayoutScheduleController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', PayoutSchedule::class);

        return view('payouts.schedules.index', [
            'schedules' => PayoutSchedule::query()->select(['id', 'program_id', 'reference_number', 'scheduled_on', 'amount', 'status'])
                ->with('program:id,name')->latest('scheduled_on')->paginate(15)->withQueryString(),
            'benefitSchedules' => BenefitSchedule::query()->select(['id', 'program_id', 'reference_number', 'scheduled_at', 'location', 'status'])
                ->with('program:id,name')->latest('id')->paginate(15, pageName: 'benefitSchedulesPage')->withQueryString(),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', PayoutSchedule::class);

        $canScheduleBenefits = $request->user()->can('manage', BenefitSchedule::class);
        $selection = $request->validate([
            'program_id' => $request->input('program_id') === 'other'
                ? ['nullable', 'in:other']
                : ['nullable', 'integer', Rule::exists('programs', 'id')],
            'schedule_id' => ['nullable', 'integer', 'exists:benefit_schedules,id'],
        ]);
        $selected = $selection['program_id'] ?? null;
        $program = filled($selected) && $selected !== 'other' ? Program::findOrFail($selected) : null;
        $useBenefitForm = $selected === 'other' || $program?->benefit_code !== null;
        $scheduleId = $selection['schedule_id'] ?? null;
        if ($scheduleId !== null) {
            abort_unless($useBenefitForm && $program && BenefitSchedule::query()->where('program_id', $program->id)->whereKey($scheduleId)->exists(), 404);
        } elseif ($program) {
            abort_unless(in_array($program->status, ['ACTIVE', 'UPCOMING'], true), 422, 'Select an active or upcoming program.');
        }
        if ($useBenefitForm && $scheduleId === null) {
            Gate::authorize('manage', BenefitSchedule::class);
        }

        return view('payouts.schedules.create', [
            'programs' => Program::query()->whereIn('status', ['ACTIVE', 'UPCOMING'])
                ->when(! $canScheduleBenefits, fn ($query) => $query->whereNull('benefit_code'))
                ->orderBy('name')->get(['id', 'name']),
            'selectedProgramId' => $selected, 'selectedProgram' => $program, 'useBenefitForm' => $useBenefitForm,
            'selectedScheduleId' => $scheduleId, 'canScheduleBenefits' => $canScheduleBenefits,
        ]);
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
                    $now = now();
                    Payout::query()->insert($beneficiaries->map(fn ($beneficiary) => [
                        'payout_schedule_id' => $schedule->id, 'senior_citizen_id' => $beneficiary->senior_citizen_id,
                        'amount' => $schedule->amount, 'status' => 'PENDING', 'created_at' => $now, 'updated_at' => $now,
                    ])->all());
                });

            AuditLog::create(['user_id' => $request->user()->id, 'action' => 'payout.schedule_created', 'auditable_type' => PayoutSchedule::class, 'auditable_id' => $schedule->id, 'ip_address' => $request->ip()]);

            return $schedule;
        });

        return redirect()->route('payout-schedules.show', $schedule)->with('status', 'Payout schedule created.');
    }

    public function show(PayoutSchedule $payoutSchedule): View
    {
        Gate::authorize('view', $payoutSchedule);

        return view('payouts.schedules.show', [
            'schedule' => $payoutSchedule->load('program:id,name'),
            'payouts' => $payoutSchedule->payouts()->select(['id', 'payout_schedule_id', 'senior_citizen_id', 'status'])
                ->with('seniorCitizen:id,first_name,middle_name,last_name,name_suffix')->latest('id')->paginate(20),
        ]);
    }
}
