<div class="space-y-6">
    @unless ($payoutMode)
        <x-staff.page-header title="Benefits Workspace" description="Prepare recipients, confirm funds, review eligibility, and trace every claim." />
    @endunless
    <x-benefit-errors />
    @can('manage', \App\Models\BenefitSchedule::class)
    @if (! $payoutMode || ! $selectedSchedule)
        <x-benefit-panel class="schedule-card">
            @if ($payoutMode)
                <div class="min-w-0">
                    <h3 class="text-lg font-semibold">Schedule details</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Prepare a draft recipient list. Review and approval are required before any payment.</p>
            @else
            <details class="min-w-0" @if(!$selectedSchedule) open @endif>
                <summary class="cursor-pointer text-lg font-semibold">Create a schedule or tracking batch</summary>
            @endif
                <form wire:submit="createSchedule" class="schedule-form schedule-editor mt-4 grid w-full grid-cols-1 items-start gap-5">
                    <div class="schedule-fields grid min-w-0 gap-4">
                    @unless ($payoutMode)
                        <div class="field-label"><label data-select-label>Program</label><x-select label="Program" wire:model.live="form.program_id" required><option value="">Select program</option>@foreach ($programs as $program)<option wire:key="schedule-program-{{ $program->id }}" value="{{ $program->id }}">{{ $program->name }}{{ $program->status === 'UPCOMING' ? ' (Upcoming)' : '' }}</option>@endforeach<option value="other">Other - enter a new program</option></x-select></div>
                    @endunless
                    @if ($form['program_id'] === 'other')
                        <fieldset class="grid grid-cols-1 gap-6 rounded-lg border border-slate-200 p-4 md:grid-cols-2 dark:border-slate-700" wire:key="new-schedule-program">
                            <legend class="px-2 font-semibold">New program</legend>
                            <label class="field-label"><span data-field-label>Program name</span><input wire:model="newProgram.name" maxlength="180" required></label>
                            <label class="field-label"><span data-field-label>Agency</span><input wire:model="newProgram.agency" maxlength="180" required></label>
                            <div class="field-label"><label data-select-label>Government level</label><x-select label="Government level" wire:model="newProgram.government_level"><option value="LGU">Local government</option><option value="PROVINCIAL">Provincial government</option><option value="NATIONAL">National government</option></x-select></div>
                            <div class="field-label"><label data-select-label>Benefit frequency</label><x-select label="Benefit frequency" wire:model.live="newProgram.cadence"><option value="ANNUAL">Annual</option><option value="QUARTERLY">Quarterly</option><option value="SEMIANNUAL">Every six months</option><option value="ON_DEMAND">Upon request</option></x-select></div>
                            <p class="text-sm text-slate-500">The program will be saved for future schedules. Verified seniors with issued OSCA IDs are eligible, subject to staff review. For milestone ages, cutoffs or additional eligibility rules, create the program in <a class="text-button" href="{{ route('benefits.programs') }}">Program rules</a> first.</p>
                        </fieldset>
                    @elseif ($selectedProgram?->provisional)
                        <div class="text-sm" wire:key="selected-program-rules-{{ $selectedProgram->id }}">
                            @if ($selectedProgram->provisional)<p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300">This program has provisional rules. Confirm the rules before scheduling.</p>@endif
                        </div>
                    @endif
                    @if ($form['program_id'] === 'other' || $selectedProgram?->status === 'UPCOMING')
                        <label class="flex items-start gap-2 text-sm"><input type="checkbox" wire:model="historicalClaimsChecked" class="mt-1 shrink-0" required><span data-field-label>I reviewed the program rules and historical claims. Activate this program when the schedule is created.</span></label>
                    @endif
                    <div class="flex flex-wrap items-start gap-3" x-data="payoutDateTime($wire.entangle('form.scheduled_at'), $wire.entangle('form.ends_at'))">
                        <label class="field-label w-full sm:w-56"><span data-field-label>Payout date</span><input x-model="date" @input="update()" type="date" :min="$wire.form.eligibility_on" data-validation-field="form.scheduled_at" required><span class="field-helper">Start and end times use this date.</span></label>
                        <x-payout-time-input label="Start time" />
                        <x-payout-time-input label="End time" :end="true" />
                        <p class="field-helper basis-full" aria-live="polite" x-text="time && endTime ? 'Payout: ' + hour + ':' + minute + ' ' + period + ' to ' + endHour + ':' + endMinute + ' ' + endPeriod + ' (Philippine time).' : 'Enter both times. End time must be later on the same payout date. Philippine time (UTC+8).'"></p>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <label class="field-label"><span data-field-label>Payout location</span><input wire:model="form.location" required maxlength="250" placeholder="Enter payout venue"></label>
                        <label class="field-label"><span data-field-label>Batch name <span class="text-xs font-normal">(optional)</span></span><input wire:model="form.batch_name" maxlength="100" placeholder="Batch 1"></label>
                    </div>
                    <section class="grid grid-cols-1 gap-x-4 gap-y-3 border-t border-[var(--line)] pt-4 sm:grid-cols-2" aria-labelledby="schedule-funding-heading">
                        <h4 id="schedule-funding-heading" class="text-base font-semibold sm:col-span-2">Funding</h4>
                        <label class="field-label"><span data-field-label>Cash aid per recipient (PHP)</span><input wire:model="form.amount" type="text" inputmode="decimal" aria-describedby="schedule-amount-help"><span id="schedule-amount-help" class="field-helper">Leave blank while awaiting funds. Milestone amounts follow program rules.</span></label>
                        <label class="field-label"><span data-field-label>Funds available for this schedule (PHP)</span><input wire:model="form.available_funds" type="text" inputmode="decimal" required aria-describedby="schedule-funds-help"><span id="schedule-funds-help" class="field-helper">Enter 0 until funding is confirmed.</span></label>
                    </section>
                    </div>
                    <section class="schedule-audience flex min-w-0 flex-col gap-4 border-t border-[var(--line)] pt-4" aria-label="Claim requirements and audience">
                    <h4 class="text-base font-semibold">Claim requirements and audience</h4>
                    <label class="field-label"><span data-field-label>Additional requirements</span><textarea wire:model="form.requirements" maxlength="1500" rows="2" placeholder="Optional: no extra documents when blank"></textarea></label>
                    <fieldset class="md:col-span-2"><legend class="mb-2 text-sm font-semibold">Barangays in this batch</legend><p class="mb-3 text-xs text-slate-500">Leave all unchecked to include all recorded barangays.</p><div class="schedule-barangays grid gap-2">
                        @foreach ($barangays as $barangay)<label wire:key="batch-barangay-{{ $barangay->id }}" class="text-sm"><input type="checkbox" wire:model="form.barangay_ids" value="{{ $barangay->id }}"> {{ $barangay->name }}</label>@endforeach
                    </div></fieldset>
                    <label class="text-sm"><input type="checkbox" wire:model="form.allow_representative"> Authorized representatives may claim under this schedule's announced instructions.</label>
                    </section>
                    <div class="staff-form-actions schedule-actions">@if ($payoutMode)<a href="{{ route('payout-schedules.index') }}" class="secondary-button">Cancel</a>@endif<x-button type="submit" wire:loading.attr="disabled">Prepare eligible recipient list</x-button><span wire:loading wire:target="createSchedule" class="text-sm" role="status">Preparing recipients...</span></div>
                </form>
            @if ($payoutMode)</div>@else</details>@endif
        </x-benefit-panel>
    @endif
    @endcan
    @unless ($payoutMode)
    <x-benefit-panel>
        <h3 class="text-lg font-semibold">Payout Schedules</h3>
        <x-staff.table label="Benefit schedules" class="mt-4"><table><thead><tr><th scope="col">Reference / Batch</th><th scope="col">Program</th><th scope="col">Period</th><th scope="col">Date / Location</th><th scope="col">Status</th><th scope="col">Recipients</th><th scope="col">Review</th></tr></thead><tbody>
            @forelse ($schedules as $schedule)
                <tr wire:key="benefit-schedule-{{ $schedule->id }}"><td>{{ $schedule->reference_number }}<p class="text-xs text-slate-500">{{ $schedule->batch_name }}</p></td><td>{{ $schedule->program->name }}<p class="text-xs text-slate-500">{{ ($schedule->rule_snapshot['government_level'] ?? $schedule->program->government_level) }}</p></td><td>{{ $schedule->period_key }}</td><td>{{ $schedule->payoutWindow() }}<p class="text-xs text-slate-500">{{ $schedule->location }}</p></td><td>{{ $schedule->status }}</td><td>{{ number_format($schedule->entitlements_count) }}</td><td><button type="button" class="text-button" wire:click="openSchedule({{ $schedule->id }})">Open</button></td></tr>
            @empty
                <tr><td colspan="7">No schedules recorded. Select a program above or choose Other to enter a new one.</td></tr>
            @endforelse
        </tbody></table></x-staff.table>{{ $schedules->links() }}
    </x-benefit-panel>
    @endunless
    @if ($selectedSchedule)<livewire:benefits.schedule-panel :schedule-id="$selectedSchedule" :key="'selected-schedule-'.$selectedSchedule" />@endif
    @unless ($payoutMode)<livewire:benefits.entitlement-report />@endunless
</div>
