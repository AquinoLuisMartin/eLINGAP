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
        @if ($senior->id_generated_at && $senior->status->value === 'VERIFIED')
            <a class="secondary-button" href="{{ route('senior-citizens.id-card', $senior) }}" target="_blank" rel="noopener">Open printable ID</a>
        @endif
        @can('manage', \App\Models\BenefitSchedule::class)
            @if (in_array($senior->status->value, ['PENDING', 'VERIFIED']))
                @if (!$senior->verified_at || $senior->status->value === 'PENDING')
                    <label class="mr-3 text-sm"><input type="checkbox" wire:model="documentsChecked"> Registration documents checked</label>
                    <button type="button" class="primary-button" wire:click="verify" wire:loading.attr="disabled">Verify registration</button>
                @elseif (!$senior->id_generated_at)
                    <button type="button" class="primary-button" wire:click="issueId" wire:loading.attr="disabled">Generate OSCA ID</button>
                @endif
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
            @endif
        @endcan
    </x-benefit-panel>
    <x-benefit-panel>
        <h2 class="text-xl font-semibold">Benefit and Payment History</h2>
        <div class="table-scroll mt-4"><table><thead><tr><th scope="col">Program</th><th scope="col">Original period</th><th scope="col">Amount</th><th scope="col">Status</th><th scope="col">Payment references</th></tr></thead><tbody>
            @forelse ($entitlements as $entry)
                <tr wire:key="senior-entitlement-{{ $entry->id }}"><td>{{ $entry->program->name }}</td><td>{{ $entry->period_key }}</td><td>{{ $entry->amount === null ? 'Awaiting funds' : 'PHP '.number_format((float) $entry->amount, 2) }}</td><td>{{ $entry->status->label() }}</td><td>@foreach ($entry->payments as $payment)<p wire:key="senior-payment-{{ $payment->id }}">{{ $payment->reference_number }} {{ $payment->reversed_at ? '(reversed)' : '' }}</p>@endforeach</td></tr>
            @empty
                <tr><td colspan="5">No benefit entitlements prepared yet.</td></tr>
            @endforelse
        </tbody></table></div>
        {{ $entitlements->links() }}
    </x-benefit-panel>
</div>
