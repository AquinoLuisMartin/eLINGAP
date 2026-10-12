<div class="space-y-6">
    <section class="page-intro"><div><p class="section-kicker">Reviewed payouts and benefit tracking</p><h2>Benefits Workspace</h2><p class="section-subtitle">Prepare recipients, confirm funds, review eligibility, and trace every claim.</p></div></section>
    <x-benefit-errors />
    @can('manage', \App\Models\BenefitSchedule::class)
        <x-benefit-panel>
            <details @if(!$selectedSchedule) open @endif>
                <summary class="cursor-pointer text-lg font-semibold">Create payout schedule or external tracking batch</summary>
                <form wire:submit="createSchedule" class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <label class="field-label">Program<select wire:model.live="form.program_id" required><option value="">Select active program</option>@foreach ($programs as $program)<option wire:key="schedule-program-{{ $program->id }}" value="{{ $program->id }}">{{ $program->name }}</option>@endforeach</select></label>
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
    @endcan
    <x-benefit-panel>
        <h3 class="text-lg font-semibold">Payout Schedules</h3>
        <div class="table-scroll mt-4"><table><thead><tr><th scope="col">Reference / Batch</th><th scope="col">Program</th><th scope="col">Period</th><th scope="col">Date / Location</th><th scope="col">Status</th><th scope="col">Recipients</th><th scope="col">Review</th></tr></thead><tbody>
            @forelse ($schedules as $schedule)
                <tr wire:key="benefit-schedule-{{ $schedule->id }}"><td>{{ $schedule->reference_number }}<p class="text-xs text-slate-500">{{ $schedule->batch_name }}</p></td><td>{{ $schedule->program->name }}<p class="text-xs text-slate-500">{{ ($schedule->rule_snapshot['government_level'] ?? $schedule->program->government_level) }}</p></td><td>{{ $schedule->period_key }}</td><td>{{ $schedule->scheduled_at->format('M j, Y g:i A') }}<p class="text-xs text-slate-500">{{ $schedule->location }}</p></td><td>{{ $schedule->status }}</td><td>{{ number_format($schedule->entitlements_count) }}</td><td><button type="button" class="text-button" wire:click="openSchedule({{ $schedule->id }})">Open</button></td></tr>
            @empty
                <tr><td colspan="7">No schedules recorded. Configure and activate a program first.</td></tr>
            @endforelse
        </tbody></table></div>{{ $schedules->links() }}
    </x-benefit-panel>
    @if ($selectedSchedule)<livewire:benefits.schedule-panel :schedule-id="$selectedSchedule" :key="'selected-schedule-'.$selectedSchedule" />@endif
    <livewire:benefits.entitlement-report />
</div>
