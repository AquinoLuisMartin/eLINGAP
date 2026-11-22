<x-benefit-panel>
    <div class="flex flex-wrap justify-between gap-3"><div><h3 class="text-xl font-semibold">Benefit Reports and Rescheduling</h3></div><button type="button" class="secondary-button" wire:click="export" wire:loading.attr="disabled">Export filtered CSV</button></div>
    <dl class="staff-summary-grid my-4 grid grid-cols-2 gap-3 xl:grid-cols-4">
        @foreach (\App\Enums\BenefitStatus::cases() as $state)<div wire:key="benefit-status-count-{{ $state->value }}" class="staff-summary-item rounded-lg border border-[var(--line)] p-3"><dt>{{ $state->label() }}</dt><dd class="mt-1 text-2xl font-semibold">{{ number_format($counts[$state->value] ?? 0) }}</dd></div>@endforeach
    </dl>
    <div class="grid gap-3 md:grid-cols-4">
        <div class="field-label"><label data-select-label>Status</label><x-select label="Status" wire:model.live="status"><option value="">All statuses</option>@foreach (\App\Enums\BenefitStatus::cases() as $state)<option wire:key="report-status-{{ $state->value }}" value="{{ $state->value }}">{{ $state->label() }}</option>@endforeach</x-select></div>
        <div class="field-label"><label data-select-label>Program</label><x-select label="Program" wire:model.live="programId"><option value="">All programs</option>@foreach($programs as $program)<option wire:key="report-program-{{ $program->id }}" value="{{ $program->id }}">{{ $program->name }}</option>@endforeach</x-select></div>
        <label class="field-label">Original period<input wire:model.live.debounce.300ms="period" placeholder="Year or milestone period"></label>
        <label class="field-label">Search senior / ID<input wire:model.live.debounce.300ms="search"></label>
    </div>

    @if ($notice)<x-feedback>{{ $notice }}</x-feedback>@endif
    <x-benefit-errors />
    @can('manage', \App\Models\BenefitSchedule::class)<div class="field-label"><label data-select-label>Target draft schedule for rescheduling</label><x-select label="Target draft schedule for rescheduling" wire:model="targetSchedule"><option value="">Select a draft for the same program</option>@foreach ($drafts as $draft)<option wire:key="reschedule-draft-{{ $draft->id }}" value="{{ $draft->id }}">{{ $draft->reference_number }} / {{ $draft->program->name }} / {{ $draft->batch_name }}</option>@endforeach</x-select></div>@endcan
    @can('reverse', \App\Models\BenefitPayment::class)<label class="field-label">Payment reversal reason<textarea wire:model="reason" minlength="10" maxlength="1000" rows="2"></textarea></label>@endcan
    <p wire:loading class="my-3 text-sm" role="status">Updating report...</p>
    <x-staff.table label="Benefit entitlements and claim history" class="mt-4"><table><thead><tr><th scope="col">Senior / Barangay</th><th scope="col">Program / Period</th><th scope="col">Amount</th><th scope="col">Status</th><th scope="col">Claim history</th><th scope="col">Action</th></tr></thead><tbody>
        @forelse ($entries as $entry)
            <tr wire:key="report-entitlement-{{ $entry->id }}"><td><a class="text-button" href="{{ route('senior-citizens.show', $entry->seniorCitizen) }}">{{ $entry->seniorCitizen->full_name }}</a><p class="text-xs text-slate-500">{{ $entry->seniorCitizen->barangay->name }} / {{ $entry->seniorCitizen->status->value }}</p></td><td>{{ $entry->program->name }}<p class="text-xs text-slate-500">{{ $entry->period_key }} / {{ $entry->program->government_level }}</p></td><td>{{ $entry->amount === null ? 'Awaiting funds' : 'PHP '.number_format((float) $entry->amount, 2) }}</td><td>{{ $entry->status->label() }}</td><td>
                @foreach ($entry->payments as $payment)<div wire:key="report-payment-{{ $payment->id }}" class="text-xs text-slate-500"><p>{{ $payment->paid_at->format('M j, Y g:i A') }}</p><p>{{ $payment->reversed_at ? 'Reversed: '.$payment->reversal_reason : 'Claimed' }}</p></div>@endforeach
            </td><td>
                @can('manage', \App\Models\BenefitSchedule::class)@if ($entry->status->value !== 'CLAIMED' && (!$entry->schedule || in_array($entry->schedule->status, ['CLOSED', 'CANCELLED'])))<button type="button" class="text-button" wire:click="reschedule({{ $entry->id }})" wire:loading.attr="disabled">Reschedule original entitlement</button>@endif @endcan
                @can('reverse', \App\Models\BenefitPayment::class)@foreach ($entry->payments as $payment)@if (!$payment->reversed_at)<button wire:key="reverse-payment-{{ $payment->id }}" type="button" class="text-button" wire:click="reverse({{ $payment->id }})" data-confirm-title="Reverse this payment?" data-confirm="The original payment and audit history are preserved." data-confirm-label="Reverse payment" data-confirm-tone="danger" wire:loading.attr="disabled">Reverse payment</button>@endif @endforeach @endcan
            </td></tr>
        @empty
            <tr><td colspan="6">No matching benefit entitlements.</td></tr>
        @endforelse
    </tbody></table></x-staff.table>{{ $entries->links() }}
</x-benefit-panel>
