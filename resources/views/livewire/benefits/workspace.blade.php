<div class="space-y-6">
    @unless ($payoutMode)
        <section class="page-intro"><div><p class="section-kicker">Reviewed payouts and benefit tracking</p><h2>Benefits Workspace</h2><p class="section-subtitle">Prepare recipients, confirm funds, review eligibility, and trace every claim.</p></div></section>
    @endunless
    <x-benefit-errors />
    @can('manage', \App\Models\BenefitSchedule::class)
    @if (! $payoutMode || ! $selectedSchedule)
        <x-benefit-panel>
            <details @if(!$selectedSchedule) open @endif>
                <summary class="cursor-pointer text-lg font-semibold">Create payout schedule or external tracking batch</summary>
                <form wire:submit="createSchedule" class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @unless ($payoutMode)
                        <label class="field-label">Program<select wire:model.live="form.program_id" required><option value="">Select program</option>@foreach ($programs as $program)<option wire:key="schedule-program-{{ $program->id }}" value="{{ $program->id }}">{{ $program->name }}{{ $program->status === 'UPCOMING' ? ' (Upcoming)' : '' }}</option>@endforeach<option value="other">Other - enter a new program</option></select></label>
                    @endunless
                    @if ($form['program_id'] === 'other')
                        <fieldset class="grid gap-4 rounded-lg border border-slate-200 p-4 md:col-span-2 xl:col-span-3 dark:border-slate-700" wire:key="new-schedule-program">
                            <legend class="px-2 font-semibold">New program</legend>
                            <label class="field-label">Program name<input wire:model="newProgram.name" maxlength="180" required></label>
                            <label class="field-label">Agency<input wire:model="newProgram.agency" maxlength="180" required></label>
                            <label class="field-label">Government level<select wire:model="newProgram.government_level"><option value="LGU">Local government</option><option value="PROVINCIAL">Provincial government</option><option value="NATIONAL">National government</option></select></label>
                            <label class="field-label">Benefit frequency<select wire:model.live="newProgram.cadence"><option value="ANNUAL">Annual</option><option value="QUARTERLY">Quarterly</option><option value="SEMIANNUAL">Every six months</option><option value="ON_DEMAND">Upon request</option></select></label>
                            <p class="text-sm text-slate-500">The program will be saved for future schedules. Verified seniors with issued OSCA IDs are eligible, subject to staff review. For milestone ages, cutoffs or additional eligibility rules, create the program in <a class="text-button" href="{{ route('benefits.programs') }}">Program rules</a> first.</p>
                        </fieldset>
                    @elseif ($selectedProgram)
                        <div class="text-sm md:col-span-2 xl:col-span-3" wire:key="selected-program-rules-{{ $selectedProgram->id }}">
                            <p>Frequency: {{ str_replace('_', ' ', $selectedProgram->benefit_rules['cadence']) }}. <a class="text-button" href="{{ route('benefits.programs') }}">Review program rules</a></p>
                            @if ($selectedProgram->provisional)<p class="mt-2 text-amber-700 dark:text-amber-300">This program has provisional rules. Confirm the rules before scheduling.</p>@endif
                        </div>
                    @endif
                    @if ($form['program_id'] === 'other' || $selectedProgram?->status === 'UPCOMING')
                        <label class="text-sm md:col-span-2 xl:col-span-3"><input type="checkbox" wire:model="historicalClaimsChecked" required> I reviewed the program rules and historical claims. Activate this program when the schedule is created.</label>
                    @endif
                    <label class="field-label">Benefit period<input wire:model="form.period_key" required><span class="field-helper">Year, YYYY-Q1, YYYY-H1, MILESTONES, or YYYY-REQUEST-1.</span></label>
                    <label class="field-label">Eligibility as of<input wire:model="form.eligibility_on" type="date" max="{{ today()->toDateString() }}" required></label>
                    <label class="field-label">Payout date and time<input wire:model="form.scheduled_at" type="datetime-local" required></label>
                    <label class="field-label">Payout location<input wire:model="form.location" required maxlength="250"></label>
                    <label class="field-label">Batch name<input wire:model="form.batch_name" maxlength="100" placeholder="Batch 1"></label>
                    <label class="field-label">Cash aid per recipient<input wire:model="form.amount" type="number" step="0.01" min="0.01"><span class="field-helper">Leave blank while awaiting funds. Milestone amounts follow program rules.</span></label>
                    <label class="field-label">Funds available for this schedule<input wire:model="form.available_funds" type="number" step="0.01" min="0" required></label>
                    <label class="field-label">Additional requirements<textarea wire:model="form.requirements" maxlength="1500" rows="2" placeholder="Optional: no extra documents when blank"></textarea></label>
                    <fieldset class="md:col-span-2 xl:col-span-3"><legend class="mb-2 text-sm font-semibold">Barangays in this batch</legend><p class="mb-3 text-xs text-slate-500">Leave all unchecked to include all recorded barangays.</p><div class="grid gap-2 sm:grid-cols-3 xl:grid-cols-4">
                        @foreach ($barangays as $barangay)<label wire:key="batch-barangay-{{ $barangay->id }}" class="text-sm"><input type="checkbox" wire:model="form.barangay_ids" value="{{ $barangay->id }}"> {{ $barangay->name }}</label>@endforeach
                    </div></fieldset>
                    <label class="text-sm md:col-span-2"><input type="checkbox" wire:model="form.allow_representative"> Authorized representatives may claim under this schedule's announced instructions.</label>
                    <div class="md:col-span-2 xl:col-span-3"><button class="primary-button" wire:loading.attr="disabled">Prepare eligible recipient list</button><span wire:loading wire:target="createSchedule" class="ml-3 text-sm" role="status">Preparing recipients...</span></div>
                </form>
            </details>
        </x-benefit-panel>
    @endif
    @endcan
    @unless ($payoutMode)
    <x-benefit-panel>
        <h3 class="text-lg font-semibold">Payout Schedules</h3>
        <div class="table-scroll mt-4"><table><thead><tr><th scope="col">Reference / Batch</th><th scope="col">Program</th><th scope="col">Period</th><th scope="col">Date / Location</th><th scope="col">Status</th><th scope="col">Recipients</th><th scope="col">Review</th></tr></thead><tbody>
            @forelse ($schedules as $schedule)
                <tr wire:key="benefit-schedule-{{ $schedule->id }}"><td>{{ $schedule->reference_number }}<p class="text-xs text-slate-500">{{ $schedule->batch_name }}</p></td><td>{{ $schedule->program->name }}<p class="text-xs text-slate-500">{{ ($schedule->rule_snapshot['government_level'] ?? $schedule->program->government_level) }}</p></td><td>{{ $schedule->period_key }}</td><td>{{ $schedule->scheduled_at->format('M j, Y g:i A') }}<p class="text-xs text-slate-500">{{ $schedule->location }}</p></td><td>{{ $schedule->status }}</td><td>{{ number_format($schedule->entitlements_count) }}</td><td><button type="button" class="text-button" wire:click="openSchedule({{ $schedule->id }})">Open</button></td></tr>
            @empty
                <tr><td colspan="7">No schedules recorded. Select a program above or choose Other to enter a new one.</td></tr>
            @endforelse
        </tbody></table></div>{{ $schedules->links() }}
    </x-benefit-panel>
    @endunless
    @if ($selectedSchedule)<livewire:benefits.schedule-panel :schedule-id="$selectedSchedule" :key="'selected-schedule-'.$selectedSchedule" />@endif
    @unless ($payoutMode)<livewire:benefits.entitlement-report />@endunless
</div>
