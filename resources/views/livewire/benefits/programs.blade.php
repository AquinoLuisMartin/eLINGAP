<div class="space-y-6" x-data @program-editor-opened.window="$nextTick(() => { $refs.editor.scrollIntoView({ block: 'start' }); $refs.editor.focus({ preventScroll: true }); })">
    @php
        $frequencies = ['ANNUAL' => 'Annual', 'QUARTERLY' => 'Quarterly', 'SEMIANNUAL' => 'Every six months', 'MILESTONE' => 'Milestone ages', 'ON_DEMAND' => 'Upon request'];
        $levels = ['LGU' => 'Local government', 'PROVINCIAL' => 'Provincial government', 'NATIONAL' => 'National government'];
    @endphp
    <x-staff.page-header title="Program rules" description="Manage program details, benefit amounts, and eligibility requirements. Provincial and national receipts are tracked separately." />
    @if ($notice)<x-feedback>{{ $notice }}</x-feedback>@endif
    <x-benefit-errors />
    <section class="flex flex-col gap-4" aria-labelledby="program-directory-heading">
        <div class="flex flex-col gap-1">
            <h3 id="program-directory-heading" class="text-lg font-semibold">Program directory <span class="ml-2 text-sm font-normal text-[var(--muted)]">{{ $programs->count() }} programs</span></h3>
            @if ($programs->contains('provisional', true))<p class="text-sm text-[var(--muted)]">Programs marked provisional require staff clarification before their rules are used for scheduling.</p>@endif
        </div>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($programs as $program)
                <article wire:key="benefit-program-{{ $program->id }}" @class(['flex min-w-0 flex-col gap-5 rounded-lg border bg-[var(--white)] p-5', 'border-[var(--line)]' => $editing !== $program->id, 'border-blue-600 ring-1 ring-blue-600 dark:border-blue-400 dark:ring-blue-400' => $editing === $program->id]) aria-labelledby="program-title-{{ $program->id }}">
                    <div class="flex flex-col gap-3">
                        <div class="flex flex-wrap items-center justify-between gap-2"><x-badge :status="$program->status" />@if ($editing === $program->id)<span class="text-xs font-medium text-blue-700 dark:text-blue-300">Editing</span>@endif</div>
                        <h4 id="program-title-{{ $program->id }}" class="break-words text-base font-semibold leading-snug text-[var(--ink)]">{{ $program->name }}</h4>
                        <p class="text-sm text-[var(--muted)]">{{ $levels[$program->government_level] ?? $program->government_level }}</p>
                    </div>
                    <dl class="grid gap-3 border-t border-[var(--line)] pt-4 text-sm">
                        <div class="flex items-baseline justify-between gap-4"><dt class="text-[var(--muted)]">Frequency</dt><dd class="text-right font-medium">{{ $frequencies[$program->benefit_rules['cadence'] ?? 'ANNUAL'] ?? 'Not configured' }}</dd></div>
                        <div class="flex items-baseline justify-between gap-4"><dt class="shrink-0 text-[var(--muted)]">Cash benefit</dt><dd class="text-right font-medium tabular-nums">{{ ($program->benefit_rules['cadence'] ?? null) === 'MILESTONE' ? 'Varies by milestone age' : ($program->default_amount ? 'PHP '.number_format((float) $program->default_amount, 2) : 'Set per funded schedule') }}</dd></div>
                    </dl>
                    <div class="mt-auto flex flex-wrap items-center justify-between gap-3 border-t border-[var(--line)] pt-4">
                        @if ($program->provisional)<span class="rounded border border-amber-200 px-2 py-1 text-xs font-medium text-amber-800 dark:border-amber-800 dark:text-amber-200">Provisional rules</span>@endif
                        @can('manage', \App\Models\BenefitSchedule::class)<x-button variant="text" class="ml-auto" wire:click="edit({{ $program->id }})" wire:loading.attr="disabled" wire:target="edit,save,cancel" aria-controls="program-editor" aria-label="Configure {{ $program->name }}">Configure program</x-button>@endcan
                    </div>
                </article>
            @empty
                <div class="rounded-lg border border-[var(--line)] bg-[var(--white)] p-8 md:col-span-2 xl:col-span-3"><h4 class="font-semibold">No programs recorded</h4><p class="mt-2 text-sm text-[var(--muted)]">Program definitions will appear here once their approved rules are recorded.</p></div>
            @endforelse
        </div>
        <p wire:loading wire:target="edit" class="text-sm text-[var(--muted)]" role="status">Loading program details...</p>
    </section>
    @can('manage', \App\Models\BenefitSchedule::class)
        <section id="program-editor" x-ref="editor" tabindex="-1" class="scroll-mt-24 rounded-lg border border-[var(--line)] bg-[var(--white)] focus-visible:outline-2 focus-visible:outline-blue-600" aria-labelledby="program-editor-heading">
            <div class="flex flex-col gap-2 border-b border-[var(--line)] p-5 sm:p-6"><h3 id="program-editor-heading" class="text-lg font-semibold">{{ $editing ? 'Edit program' : 'Create program' }}</h3><p class="break-words text-sm text-[var(--muted)]">{{ $editing ? $form['name'] : 'Record the approved program details and requirements.' }}</p></div>
            <form wire:submit="save" class="flex flex-col gap-6 p-5 sm:p-6">
                <fieldset class="grid min-w-0 gap-4 md:grid-cols-2">
                    <legend class="mb-4 text-base font-semibold">Program details</legend>
                    @foreach (['name' => 'Program name', 'agency' => 'Agency'] as $field => $label)
                        <x-form-field wire:key="program-field-{{ $field }}" name="form.{{ $field }}" :label="$label" required>
                            <input id="form-{{ $field }}" wire:model="form.{{ $field }}" maxlength="180" required aria-invalid="{{ $errors->has('form.'.$field) ? 'true' : 'false' }}" @error('form.'.$field) aria-describedby="form-{{ $field }}-error" @enderror>
                        </x-form-field>
                    @endforeach
                    @foreach (['government_level' => ['Government level', $levels], 'status' => ['Status', ['UPCOMING' => 'Upcoming', 'ACTIVE' => 'Active', 'INACTIVE' => 'Inactive']]] as $field => [$label, $options])
                        <x-form-field wire:key="program-field-{{ $field }}" name="form.{{ $field }}" :label="$label" required>
                            <x-select id="form-{{ $field }}" :label="$label" wire:model="form.{{ $field }}" :aria-describedby="$errors->has('form.'.$field) ? 'form-'.$field.'-error' : null">@foreach ($options as $value => $text)<option value="{{ $value }}">{{ $text }}</option>@endforeach</x-select>
                        </x-form-field>
                    @endforeach
                </fieldset>
                <fieldset class="grid min-w-0 gap-4 border-t border-[var(--line)] pt-5 md:grid-cols-2">
                    <legend class="px-1 text-base font-semibold">Benefit amounts and timing</legend>
                    <x-form-field name="form.cadence" label="Benefit frequency" required>
                        <x-select id="form-cadence" label="Benefit frequency" wire:model.live="form.cadence" :aria-describedby="$errors->has('form.cadence') ? 'form-cadence-error' : null">@foreach ($frequencies as $value => $label)<option wire:key="cadence-{{ $value }}" value="{{ $value }}">{{ $label }}</option>@endforeach</x-select>
                    </x-form-field>
                    @foreach (['default_amount' => ['Default cash amount (optional)', 'number', 'Leave blank to set the amount per funded schedule. Milestone benefits use age-based amounts.'], 'cutoff' => ['Verification cutoff (optional)', 'text', 'Enter month and day as MM-DD.'], 'payout_month' => ['Payout month (optional)', 'number', 'Enter a month from 1 (January) to 12 (December).'], 'claim_window_months' => ['Milestone claim window (optional)', 'number', 'Number of months allowed for milestone claiming, from 1 to 120.']] as $field => [$label, $type, $hint])
                        <x-form-field wire:key="program-field-{{ $field }}" name="form.{{ $field }}" :label="$label" :hint="$hint">
                            <input id="form-{{ $field }}" wire:model="form.{{ $field }}" type="{{ $type }}" @if($field === 'default_amount') step="0.01" min="0.01" max="9999999999.99" @elseif($field === 'payout_month') min="1" max="12" @elseif($field === 'claim_window_months') min="1" max="120" @endif aria-invalid="{{ $errors->has('form.'.$field) ? 'true' : 'false' }}" aria-describedby="form-{{ $field }}-help{{ $errors->has('form.'.$field) ? ' form-'.$field.'-error' : '' }}">
                        </x-form-field>
                    @endforeach
                    @if ($form['cadence'] === 'MILESTONE')
                        <x-form-field name="form.milestones" label="Milestone ages and cash amounts" hint="Enter one age:amount pair per line, for example 80:10000. Ages must be between 60 and 120." class="md:col-span-2">
                            <textarea id="form-milestones" wire:model="form.milestones" rows="5" maxlength="1000" aria-invalid="{{ $errors->has('form.milestones') ? 'true' : 'false' }}" aria-describedby="form-milestones-help{{ $errors->has('form.milestones') ? ' form-milestones-error' : '' }}"></textarea>
                        </x-form-field>
                    @endif
                </fieldset>
                <fieldset class="grid min-w-0 gap-4 border-t border-[var(--line)] pt-5 md:grid-cols-2">
                    <legend class="px-1 text-base font-semibold">Eligibility requirements</legend>
                    @foreach (['october_conditions' => 'Requires bedridden, indigent, and neither pension', 'office_review' => 'Requires office request and verification', 'groceries' => 'Includes groceries', 'provisional' => 'Rules remain provisional'] as $field => $label)
                        <div wire:key="rule-{{ $field }}"><label class="flex items-start gap-3 text-sm"><input class="mt-0.5" type="checkbox" wire:model="form.{{ $field }}"> <span>{{ $label }}</span></label>@error('form.'.$field)<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
                    @endforeach
                </fieldset>
                <div class="flex flex-col gap-3 rounded-lg border border-[var(--line)] p-4">
                    <label class="flex items-start gap-3 text-sm"><input class="mt-0.5" type="checkbox" wire:model="historicalClaimsChecked" aria-describedby="historical-claims-help{{ $errors->has('historicalClaimsChecked') ? ' historical-claims-error' : '' }}"> <span>I reviewed historical claims and confirm these rules.</span></label>
                    <p id="historical-claims-help" class="text-sm text-[var(--muted)]">Existing entitlement and payout amounts will remain unchanged.</p>
                    @error('historicalClaimsChecked')<p id="historical-claims-error" class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-wrap items-center gap-3 border-t border-[var(--line)] pt-5">
                    <x-button type="submit" wire:loading.attr="disabled" wire:target="save,edit,cancel">Save program</x-button>
                    <x-button variant="secondary" wire:click="cancel" wire:loading.attr="disabled" wire:target="save,edit,cancel">{{ $editing ? 'Cancel editing' : 'Reset form' }}</x-button>
                    <span wire:loading wire:target="save" class="text-sm text-[var(--muted)]" role="status">Saving program...</span>
                    <span wire:loading wire:target="cancel" class="text-sm text-[var(--muted)]" role="status">Resetting form...</span>
                </div>
            </form>
        </section>
    @endcan
</div>
