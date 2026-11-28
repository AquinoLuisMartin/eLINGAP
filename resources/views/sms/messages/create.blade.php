@extends('layouts.app')

@section('content')
    @php($isBirthday = isset($birthday))
    @php($sendDisabled = $isBirthday ? 'sending' : "sending || (mode === 'program' && !selected)")
    <x-workspace-navigation section="messaging" />
    <x-staff.page-header :title="$isBirthday ? 'Send birthday greeting' : 'Send individual SMS'" />

    <form method="POST" action="{{ $isBirthday ? route('sms.messages.birthday.store', $birthday['senior']) : route('sms.messages.store') }}" class="grid max-w-6xl items-start gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]"
        @if ($isBirthday)
            x-data="{ sending: false }"
        @else
            x-data="individualSmsComposer({ url: @js(route('sms.messages.recipients')), program: @js($programId), selected: @js($selected), manual: @js(old('recipient_number') !== null && ! $programId) })"
        @endif
        x-on:submit="if (sending) $event.preventDefault(); else sending = true"
        data-confirm-title="Send this SMS now?" data-confirm="The message will be submitted to the SMS provider when you confirm." data-confirm-label="Send SMS">
        @csrf

        <section class="panel grid gap-4" aria-labelledby="individual-sms-recipient">
            <h3 id="individual-sms-recipient" class="text-lg">Recipient</h3>
            @if ($isBirthday)
                <div class="grid gap-1">
                    <strong>{{ $birthday['senior']->full_name }}</strong>
                    <span class="text-sm text-[var(--muted)]">{{ $birthday['senior']->osca_id_number }}</span>
                </div>
                <input type="hidden" name="birthday_year" value="{{ $birthday['year'] }}">
                <label class="field-label">Registered mobile number
                    <input name="recipient_number" type="tel" value="{{ $birthday['display_number'] }}" readonly>
                </label>
                @error('recipient_number')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            @else
            <fieldset class="flex flex-wrap gap-x-5 gap-y-2">
                <legend class="mb-2 text-sm">Recipient source</legend>
                <label class="flex items-center gap-2 text-sm"><input type="radio" value="program" x-model="mode"> Program masterlist</label>
                <label class="flex items-center gap-2 text-sm"><input type="radio" value="manual" x-model="mode"> Enter number manually</label>
            </fieldset>

            <div x-cloak x-show="mode === 'program'" class="grid gap-4">
                <div class="field-label">
                    <label data-select-label>Program</label>
                    <x-select label="Program" name="program_id" x-model="program" x-on:change="changeProgram($event.target.value)" x-bind:disabled="mode !== 'program'" x-bind:required="mode === 'program'">
                        <option value="">Select a program</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" @selected($programId === $program->id)>{{ $program->name }}</option>
                        @endforeach
                    </x-select>
                    @error('program_id')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div x-show="program" class="grid gap-3">
                    <div class="flex items-end gap-2">
                        <label class="field-label min-w-0 flex-1">Find a beneficiary
                            <input type="search" x-model="search" x-on:keydown.enter.prevent="lookup()" maxlength="100" placeholder="Name or OSCA ID">
                        </label>
                        <button type="button" class="secondary-button" x-on:click="lookup()" x-bind:disabled="loading">Search</button>
                    </div>
                    <p x-show="loading" role="status" class="text-sm text-[var(--muted)]">Loading beneficiaries…</p>
                    <p x-show="error" x-text="error" role="alert" class="field-error"></p>
                    <p x-show="loaded && !recipients.length" role="status" class="text-sm text-[var(--muted)]">No matching beneficiaries with a valid mobile number.</p>
                    <p x-show="hasMore" class="text-xs text-[var(--muted)]">Showing the first 15 matches. Search by name or OSCA ID to narrow the list.</p>
                    <ul x-show="recipients.length" class="max-h-60 divide-y divide-[var(--line)] overflow-y-auto rounded-lg border border-[var(--line)]" aria-label="Matching beneficiaries">
                        <template x-for="recipient in recipients" x-bind:key="recipient.id">
                            <li>
                                <button type="button" class="flex w-full flex-wrap items-center justify-between gap-x-3 gap-y-1 px-3 py-2.5 text-left text-sm hover:bg-[var(--paper)] focus-visible:-outline-offset-2"
                                    x-bind:aria-pressed="selected?.id === recipient.id" x-on:click="selected = recipient">
                                    <span class="min-w-0 wrap-anywhere" x-text="recipient.name"></span>
                                    <span class="text-xs text-[var(--muted)]" x-text="recipient.osca_id"></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                    <div x-show="selected" class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-[var(--line)] bg-[var(--paper)] px-3 py-2 text-sm" aria-live="polite">
                        <span>Selected <strong x-text="selected?.name"></strong></span>
                        <button type="button" class="text-button" x-on:click="selected = null">Clear</button>
                    </div>
                </div>
                <input type="hidden" name="senior_citizen_id" x-bind:value="selected?.id ?? ''" x-bind:disabled="mode !== 'program'">
                @error('senior_citizen_id')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>

            <div x-show="mode === 'manual'" class="grid gap-2">
                <label class="field-label">Philippine mobile number
                    <input name="recipient_number" type="tel" value="{{ is_string(old('recipient_number')) ? old('recipient_number') : '' }}" required x-bind:required="mode === 'manual'" x-bind:disabled="mode !== 'manual'" placeholder="09xx xxx xxxx">
                </label>
                @error('recipient_number')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>
            @endif
        </section>

        <section class="panel grid gap-4" aria-labelledby="individual-sms-message" x-data="{ length: 0 }" x-init="length = $refs.message.value.length">
            <h3 id="individual-sms-message" class="text-lg">Compose message</h3>
            @if ($isBirthday)
                <p class="text-sm text-[var(--muted)]">{{ $birthday['template']->name }} template</p>
            @endif
            <label class="field-label">Message
                <textarea name="message" x-ref="message" x-on:input="length = $event.target.value.length" rows="7" required maxlength="1600" aria-describedby="individual-sms-count" placeholder="Write your SMS">{{ is_string(old('message')) ? old('message') : ($birthday['message'] ?? '') }}</textarea>
            </label>
            <p id="individual-sms-count" class="text-right text-xs tabular-nums text-[var(--muted)]"><span x-text="length"></span> / 1,600 characters</p>
            @error('message')<p class="field-error" role="alert">{{ $message }}</p>@enderror

            <div class="flex flex-wrap justify-end gap-3 border-t border-[var(--line)] pt-4">
                <a class="secondary-button" href="{{ route('sms.messages.index') }}">Cancel</a>
                <x-button type="submit" x-bind:disabled="{{ $sendDisabled }}" class="disabled:cursor-not-allowed disabled:opacity-50"><span x-text="sending ? 'Sending…' : 'Send SMS'">Send SMS</span></x-button>
            </div>
        </section>
    </form>
@endsection
