<div class="space-y-6">
    <section class="page-intro">
        <div><p class="section-kicker">Central benefits registry</p><h2>Program Rules</h2><p class="section-subtitle">LGU payouts are prioritized. Provincial and national receipt records are tracked separately.</p></div>
        @can('manage', \App\Models\BenefitSchedule::class)
            <button type="button" class="primary-button" wire:click="installCatalog" wire:loading.attr="disabled">Initialize approved programs</button>
        @endcan
    </section>
    @if ($notice)<p class="rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200" role="status">{{ $notice }}</p>@endif
    <x-benefit-errors />
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($programs as $program)
            <x-benefit-panel wire:key="benefit-program-{{ $program->id }}">
                <div class="flex flex-wrap justify-between gap-2"><x-badge :status="$program->status" /><span class="text-xs text-slate-500">{{ $program->government_level }}</span></div>
                <h3 class="mt-3 text-lg font-semibold">{{ $program->name }}</h3>
                <p class="mt-2 text-sm text-slate-500">{{ $program->benefit_rules['cadence'] }} / {{ $program->default_amount ? 'PHP '.number_format((float) $program->default_amount, 2) : 'Amount set per funded schedule' }}</p>
                @if ($program->provisional)<p class="mt-3 rounded bg-amber-50 p-2 text-xs text-amber-800 dark:bg-amber-950 dark:text-amber-200">Provisional rules: subject to staff clarification.</p>@endif
                @can('manage', \App\Models\BenefitSchedule::class)<button type="button" class="text-button mt-4" wire:click="edit({{ $program->id }})">Configure program</button>@endcan
            </x-benefit-panel>
        @empty
            <x-benefit-panel><p>No program definitions recorded. Staff can initialize the approved catalog.</p></x-benefit-panel>
        @endforelse
    </div>
    @can('manage', \App\Models\BenefitSchedule::class)
        <x-benefit-panel>
            <h3 class="text-lg font-semibold">{{ $editing ? 'Edit program' : 'Create program' }}</h3>
            <form wire:submit="save" class="mt-4 grid gap-4 md:grid-cols-2">
                @foreach (['name' => 'Program name', 'agency' => 'Agency', 'default_amount' => 'Default cash amount (optional)', 'cutoff' => 'Verification cutoff (MM-DD)', 'payout_month' => 'Payout month (1-12, optional)', 'claim_window_months' => 'Milestone claim window in months (optional)'] as $field => $label)
                    <label wire:key="program-field-{{ $field }}" class="field-label">{{ $label }}<input wire:model="form.{{ $field }}" type="{{ in_array($field, ['default_amount', 'payout_month', 'claim_window_months']) ? 'number' : 'text' }}" @if($field === 'default_amount') step="0.01" @endif></label>
                @endforeach
                <label class="field-label">Government level<select wire:model="form.government_level"><option>LGU</option><option>PROVINCIAL</option><option>NATIONAL</option></select></label>
                <label class="field-label">Status<select wire:model="form.status"><option>UPCOMING</option><option>ACTIVE</option><option>INACTIVE</option></select></label>
                <label class="field-label">Cadence<select wire:model.live="form.cadence">@foreach (['ANNUAL', 'QUARTERLY', 'SEMIANNUAL', 'MILESTONE', 'ON_DEMAND'] as $cadence)<option wire:key="cadence-{{ $cadence }}">{{ $cadence }}</option>@endforeach</select></label>
                @if ($form['cadence'] === 'MILESTONE')<label class="field-label">Milestone ages and amounts, one age:amount per line<textarea wire:model="form.milestones" rows="5" placeholder="90:5000"></textarea></label>@endif
                <div class="flex flex-col gap-3 text-sm">
                    @foreach (['october_conditions' => 'Requires bedridden, indigent, and neither pension', 'office_review' => 'Requires office request and verification', 'groceries' => 'Includes groceries', 'provisional' => 'Rules remain provisional'] as $field => $label)
                        <label wire:key="rule-{{ $field }}"><input type="checkbox" wire:model="form.{{ $field }}"> {{ $label }}</label>
                    @endforeach
                </div>
                <label class="text-sm md:col-span-2"><input type="checkbox" wire:model="historicalClaimsChecked"> I reviewed historical claims and confirm these rules. Existing entitlement amounts will not change.</label>
                <div class="md:col-span-2"><button class="primary-button" wire:loading.attr="disabled">Save program</button><span wire:loading wire:target="save" class="ml-3 text-sm" role="status">Saving...</span></div>
            </form>
        </x-benefit-panel>
    @endcan
</div>
