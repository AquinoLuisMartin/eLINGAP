<div class="mt-6 space-y-4">
    <x-benefit-panel>
        <h2 class="text-xl font-semibold">Central Eligibility</h2>
        <p class="mt-2 text-sm text-slate-500">One verified registration and generated OSCA ID covers applicable programs. Staff review program conditions before payment.</p>
        @if ($notice)<p class="my-3 text-sm text-emerald-700 dark:text-emerald-300" role="status">{{ $notice }}</p>@endif
        <x-benefit-errors />
        <dl class="my-4 grid gap-3 sm:grid-cols-3">
            <div><dt class="text-sm text-slate-500">Verified</dt><dd>{{ $senior->verified_at?->format('M j, Y') ?? 'Not verified' }}</dd></div>
            <div><dt class="text-sm text-slate-500">ID issued</dt><dd>{{ $senior->id_generated_at?->format('M j, Y') ?? 'Not recorded' }}</dd></div>
            <div><dt class="text-sm text-slate-500">OSCA ID</dt><dd>{{ $senior->osca_id_number ?? 'Not generated' }}</dd></div>
        </dl>
        <h3 class="font-semibold">Assigned programs and benefits</h3>
        <ul class="mt-2 space-y-2 text-sm">
            @forelse ($senior->activeAssignments as $assignment)
                <li wire:key="assigned-benefit-{{ $assignment->id }}">{{ $assignment->program->name }}@if($assignment->assigned_at) <span class="text-slate-500">Assigned {{ $assignment->assigned_at->format('M j, Y') }}</span>@endif</li>
            @empty
                <li class="text-slate-500">No programs assigned.</li>
            @endforelse
        </ul>
        @can('manage', \App\Models\BenefitSchedule::class)
            @if ($senior->status->value === 'VERIFIED' && $senior->id_generated_at && $senior->osca_id_number)
                <form wire:submit="assignBenefit" class="mt-4 flex flex-wrap items-end gap-3">
                    <label class="field-label">Program or benefit<select wire:model="programId" required><option value="">Select program</option>@foreach ($programs as $program)<option wire:key="assignment-program-{{ $program->id }}" value="{{ $program->id }}">{{ $program->name }}</option>@endforeach</select></label>
                    <button type="submit" class="primary-button" wire:loading.attr="disabled">Assign benefit</button>
                </form>
                <form wire:submit="saveClassifications" class="mt-6 space-y-3">
                    <h3 class="font-semibold">Staff-verified classifications</h3>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach (['is_bedridden' => 'Bedridden', 'is_indigent' => 'Indigent', 'has_national_pension' => 'Receives national pension', 'has_local_pension' => 'Receives local pension'] as $field => $label)
                            <label wire:key="classification-{{ $field }}" class="text-sm"><input type="checkbox" wire:model="classifications.{{ $field }}"> {{ $label }}</label>
                        @endforeach
                    </div>
                    <label class="field-label">TSS office request and verification note (leave blank when not verified)<textarea wire:model="classifications.tss_request" maxlength="500"></textarea></label>
                    <button class="secondary-button" wire:loading.attr="disabled">Record verified classifications</button>
                </form>
            @else
                <p class="mt-4 text-sm text-slate-500">Register, verify and generate the OSCA ID before assigning benefits or classifications.</p>
            @endif
        @endcan
    </x-benefit-panel>
    <x-benefit-panel>
        <h2 class="text-xl font-semibold">Benefit and Payment History</h2>
        <x-staff.table label="Senior benefit history" class="mt-4"><table><thead><tr><th scope="col">Program</th><th scope="col">Original period</th><th scope="col">Amount</th><th scope="col">Status</th><th scope="col">Payment references</th></tr></thead><tbody>
            @forelse ($entitlements as $entry)
                <tr wire:key="senior-entitlement-{{ $entry->id }}"><td>{{ $entry->program->name }}</td><td>{{ $entry->period_key }}</td><td>{{ $entry->amount === null ? 'Awaiting funds' : 'PHP '.number_format((float) $entry->amount, 2) }}</td><td>{{ $entry->status->label() }}</td><td>@foreach ($entry->payments as $payment)<p wire:key="senior-payment-{{ $payment->id }}">{{ $payment->reference_number }} {{ $payment->reversed_at ? '(reversed)' : '' }}</p>@endforeach</td></tr>
            @empty
                <tr><td colspan="5">No benefit entitlements prepared yet.</td></tr>
            @endforelse
        </tbody></table></x-staff.table>
        {{ $entitlements->links() }}
    </x-benefit-panel>
</div>
