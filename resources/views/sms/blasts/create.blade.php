@extends('layouts.app')

@section('content')
    <x-workspace-navigation section="messaging" />
    <x-staff.page-header :title="$selectedPayout ? 'Payout SMS announcement' : 'New SMS broadcast'">
        <a href="{{ route('sms.blasts.index') }}" class="primary-button">View SMS History</a>
    </x-staff.page-header>
    @can('manage', \App\Models\BenefitSchedule::class)
        <form method="GET" action="{{ route('sms.blasts.create') }}" class="panel mb-5 flex flex-wrap items-end gap-4">
            <div class="ui-field min-w-0 flex-1">
                <label for="messaging-payout">Scheduled payout</label>
                <x-select id="messaging-payout" label="Scheduled payout" name="benefit_schedule_id">
                    <option value="">General broadcast</option>
                    @foreach ($payoutSchedules as $payout)
                        <option value="{{ $payout->id }}" @selected($selectedPayout?->id === $payout->id)>{{ $payout->program->name }} / {{ $payout->reference_number }} / {{ $payout->payoutWindow() }} / {{ $payout->location }} / {{ $payout->status }}</option>
                    @endforeach
                </x-select>

            </div>
            <button type="submit" class="primary-button">Open message</button>
        </form>
    @endcan
    @if ($selectedPayout)
        <livewire:benefits.schedule-panel :schedule-id="$selectedPayout->id" :sms-only="true" :key="'payout-sms-'.$selectedPayout->id" />
    @else
    @php($templateData = $templates->map(fn ($template) => ['id' => (string) $template->id, 'title' => $template->name, 'body' => $template->body, 'active' => (bool) $template->is_active, 'programId' => $template->program_id ?? $programs->first(fn ($program) => $template->name === $program->name || str_starts_with($template->name, \Illuminate\Support\Str::limit($program->name, 85).' - '))?->id])->values())
    <div x-data="broadcastComposer({ eligible: @js($eligible), excluded: @js($excluded), barangay: @js((string) request('barangay_id', '')), program: @js((string) request('program_id', '')), audienceUrl: @js(route('sms.blasts.create')), key: @js('elingap-sms-templates-'.auth()->id()) })"
        data-templates="{{ json_encode($templateData, JSON_THROW_ON_ERROR) }}" data-retired-templates="{{ json_encode($retiredTemplates, JSON_THROW_ON_ERROR) }}"
        x-on:sms-templates-updated.window="refreshTemplates($event)" x-on:storage.window="refreshTemplates($event)">

    <div class="grid items-start gap-5 xl:grid-cols-12">
        <aside class="panel xl:order-last xl:col-span-4" aria-labelledby="broadcast-audience-title">
            <h3 id="broadcast-audience-title" class="text-lg font-semibold">Audience</h3>
            <p class="mt-2 text-sm text-[var(--muted)]">Verified seniors with valid mobile numbers only.</p>
            <div class="mt-5 grid gap-4">
                <div class="field-label"><label data-select-label>Target barangay</label><x-select label="Target barangay" id="broadcast-barangay" x-model="barangay">
                        <option value="">All barangays</option>
                        @foreach ($barangays as $barangay)<option value="{{ $barangay->id }}" @selected(request('barangay_id') == $barangay->id)>{{ $barangay->name }}</option>@endforeach
                    </x-select>
                </div>
                <div class="field-label">
                    <label data-select-label>Benefit / Pension Program</label>
                    <x-select label="Benefit / Pension Program" id="broadcast-program" x-model="program">
                        <option value="">All programs</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" @selected(request('program_id') == $program->id)>{{ $program->name }}</option>
                        @endforeach
                    </x-select>
                </div>
            </div>
            <dl class="mt-5 grid grid-cols-2 gap-4 border-t border-[var(--line)] pt-5">
                <div><dt class="text-sm text-[var(--muted)]">Eligible recipients</dt><dd class="mt-2 text-2xl font-semibold tabular-nums" aria-live="polite"><strong x-text="audiencePreview ? '—' : eligibleRecipients">{{ $eligible }}</strong></dd></div>
                <div><dt class="text-sm text-[var(--muted)]">Excluded</dt><dd class="mt-2 text-2xl font-semibold tabular-nums" aria-live="polite"><strong x-text="audiencePreview ? '—' : excludedRecipients">{{ $excluded }}</strong></dd></div>
            </dl>
            <p class="mt-4 text-sm text-[var(--muted)]">Excluded: unverified records or invalid mobile numbers.</p>

            <p x-cloak x-show="audienceLoading" role="status" class="mt-3 text-sm">Loading recipients...</p>
            <div x-cloak x-show="audienceError" class="mt-3 text-sm"><p x-text="audienceError" role="alert"></p><button type="button" class="text-button" x-on:click="refreshAudience()">Retry</button></div>
        </aside>

        <form method="POST" action="{{ route('sms.blasts.store') }}" id="sms-broadcast-form" :data-eligible="eligibleRecipients" :data-excluded="excludedRecipients" :data-audience="(barangay ? document.getElementById('broadcast-barangay').selectedOptions[0]?.text : 'All barangays') + ' / ' + (program ? document.getElementById('broadcast-program').selectedOptions[0]?.text : 'All programs')" class="panel grid gap-5 xl:col-span-8" x-on:submit="if (audiencePreview) $event.preventDefault()">
            @csrf
            <input type="hidden" name="barangay_id" :value="barangay" value="{{ request('barangay_id') }}">
            <input type="hidden" name="program_id" :value="program" value="{{ request('program_id') }}">
            <h3 class="text-lg font-semibold">Compose message</h3>
            <div class="field-label"><label data-select-label>Quick template</label><x-select label="Quick template" id="broadcast-template" x-model="selectedTemplate" x-on:change="chooseTemplate($event.target.value)">
                    <option value="">Select a template</option>
                    <template x-for="quickTemplate in programTemplates()" :key="quickTemplate.id">
                        <option :value="quickTemplate.id" x-text="quickTemplate.title"></option>
                    </template>
                </x-select>
            </div>
            <p x-cloak x-show="program && programTemplates().length === 0" class="text-sm text-[var(--muted)]">No template for this program.</p>
            <label class="field-label">Message
                <textarea id="broadcast-message" x-ref="message" x-model="broadcastBody" name="message" rows="8" maxlength="1600" required aria-describedby="sms-count" placeholder="Write a message"></textarea>
            </label>
            <p id="sms-count" class="text-sm text-[var(--muted)]" aria-live="polite">0 characters</p>
            <label class="flex items-start gap-3 rounded-lg border border-[var(--line)] p-4 text-sm"><input x-ref="confirmed" type="checkbox" name="confirmed" value="1" required class="mt-1"> <span>I reviewed the audience and message, and confirm this broadcast.</span></label>
            <div class="staff-form-actions"><button type="submit" class="primary-button" :disabled="audiencePreview || eligibleRecipients === 0">Queue broadcast</button><a href="{{ route('sms.blasts.index') }}" class="secondary-button">Cancel</a></div>
        </form>
    </div>
    </div>
    @endif
@endsection
