@extends('layouts.app')

@section('content')
    <div x-data="broadcastComposer({ eligible: @js($eligible), message: @js(old('message', '')) })">
    <x-workspace-navigation section="messaging" />
    <x-staff.page-header title="New SMS broadcast" description="Select an audience, review your message, then confirm it for delivery.">
        <a href="{{ route('sms.blasts.index') }}" class="secondary-button">View SMS History</a>
    </x-staff.page-header>

    <div class="grid items-start gap-5 xl:grid-cols-12">
        <aside class="panel xl:order-last xl:col-span-4" aria-labelledby="broadcast-audience-title">
            <h3 id="broadcast-audience-title" class="text-lg font-semibold">Audience</h3>
            <p class="mt-2 text-sm text-[var(--muted)]">Only verified seniors with valid mobile numbers are included.</p>
            <form method="GET" class="mt-5 grid gap-4">
                <div class="field-label"><label data-select-label>Target barangay</label><x-select label="Target barangay" name="barangay_id">
                        <option value="">All barangays</option>
                        @foreach ($barangays as $barangay)<option value="{{ $barangay->id }}" @selected(request('barangay_id') == $barangay->id)>{{ $barangay->name }}</option>@endforeach
                    </x-select>
                </div>
                <div class="field-label">
                    <label data-select-label>Benefit / Pension Program</label>
                    <x-select label="Benefit / Pension Program" id="broadcast-program" x-model="program" aria-describedby="program-preview-note">
                        <option value="all">All Programs</option>
                        <option value="sss">SSS Pension</option>
                        <option value="gsis">GSIS Pension</option>
                        <option value="lgu">LGU Social Pension</option>
                        <option value="centenarian">Centenarian Cash Gift</option>
                    </x-select>
                </div>
                <button class="secondary-button">Update audience</button>
            </form>
            <dl class="mt-5 grid grid-cols-2 gap-4 border-t border-[var(--line)] pt-5">
                <div><dt class="text-sm text-[var(--muted)]">Eligible recipients <span x-cloak x-show="program !== 'all'">(mock preview)</span></dt><dd class="mt-2 text-2xl font-semibold tabular-nums" aria-live="polite"><strong x-text="eligibleRecipients">{{ $eligible }}</strong></dd></div>
                <div><dt class="text-sm text-[var(--muted)]">Excluded</dt><dd class="mt-2 text-2xl font-semibold tabular-nums"><strong>{{ $excluded }}</strong></dd></div>
            </dl>
            <p class="mt-4 text-sm text-[var(--muted)]">Excluded records are ineligible or have a missing or invalid mobile number.</p>
            <p id="program-preview-note" class="mt-3 text-sm text-[var(--muted)]">Program counts are mock previews. Select All Programs to queue a broadcast using the selected barangay.</p>
        </aside>

        <form method="POST" action="{{ route('sms.blasts.store') }}" id="sms-broadcast-form" data-eligible="{{ $eligible }}" data-excluded="{{ $excluded }}" data-audience="{{ $barangays->firstWhere('id', request('barangay_id'))?->name ?: 'All barangays' }}" class="panel grid gap-5 xl:col-span-8" x-on:submit="if (program !== 'all') $event.preventDefault()">
            @csrf
            <input type="hidden" name="barangay_id" value="{{ request('barangay_id') }}">
            <h3 class="text-lg font-semibold">Compose message</h3>
            <div class="field-label"><label data-select-label>Quick template</label><x-select label="Quick template" id="broadcast-template" x-model="selectedTemplate" x-on:change="chooseTemplate($event)">
                    <option value="">-- Select a template or create new --</option>
                    @foreach ($templates as $template)<option value="{{ $template->id }}" data-body="{{ $template->body }}">{{ $template->name }}</option>@endforeach
                    <template x-for="template in localTemplates" :key="template.id">
                        <option :value="template.id" :data-body="template.body" x-text="template.title"></option>
                    </template>
                    <option value="__new__">+ Create new template...</option>
                </x-select>
            </div>
            <label class="field-label">Message
                <textarea id="broadcast-message" x-ref="message" x-model="broadcastBody" name="message" rows="8" maxlength="1600" required aria-describedby="sms-count sms-placeholder-note" placeholder="[OSCA BROADCAST] Paalala sa mga senior citizens: May payout/announcement sa (Insert Venue/Place) sa (Insert Date) ganap na (Insert Time). Mangyaring magdala ng inyong OSCA ID.">{{ old('message') }}</textarea>
            </label>
            <p id="sms-placeholder-note" class="text-sm text-[var(--muted)]">Replace (Insert Venue/Place), (Insert Date), and (Insert Time) with confirmed details before sending.</p>
            <p id="sms-count" class="text-sm text-[var(--muted)]" aria-live="polite">Enter a message to see SMS segment usage.</p>
            <label class="flex items-start gap-3 rounded-lg border border-[var(--line)] p-4 text-sm"><input type="checkbox" name="confirmed" value="1" required class="mt-1"> <span>I reviewed the audience and message, and confirm this broadcast.</span></label>
            <div class="staff-form-actions"><button type="submit" class="primary-button" :disabled="program !== 'all'" aria-describedby="program-preview-note">Queue broadcast</button><a href="{{ route('sms.blasts.index') }}" class="secondary-button">Cancel</a></div>
        </form>
    </div>
    <dialog x-ref="templateDialog" aria-labelledby="new-template-title" class="m-auto max-h-[85dvh] w-[calc(100%-2rem)] max-w-2xl overflow-y-auto rounded-xl border border-slate-200 bg-white p-6 text-slate-900 shadow-xl backdrop:bg-slate-950/50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
        <form x-on:submit.prevent="saveTemplate()" class="flex flex-col gap-5">
            <div><h2 id="new-template-title" class="text-xl font-semibold">Create new template</h2><p class="mt-2 text-sm text-slate-500">This template is available on this page until you reload or leave.</p></div>
            <label class="field-label">Template Title<input x-model="templateTitle" maxlength="120" required autofocus></label>
            <label class="field-label">Message Body<textarea x-model="templateBody" rows="6" maxlength="1600" required></textarea></label>
            <p x-cloak x-show="templateError" x-text="templateError" role="alert" class="text-sm text-red-700 dark:text-red-300"></p>
            <div class="flex flex-wrap justify-end gap-3">
                <button type="button" class="secondary-button" x-on:click="$refs.templateDialog.close()">Cancel</button>
                <button type="submit" class="primary-button">Use template</button>
            </div>
        </form>
    </dialog>
    </div>
@endsection
