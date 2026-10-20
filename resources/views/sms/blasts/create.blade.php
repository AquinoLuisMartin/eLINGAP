@extends('layouts.app')

@section('content')
    <x-workspace-navigation section="messaging" />
    <x-staff.page-header title="New SMS broadcast" description="Select an audience, review your message, then confirm it for delivery.">
        <a href="{{ route('sms.blasts.index') }}" class="secondary-button">Back to messaging</a>
    </x-staff.page-header>

    <div class="grid items-start gap-5 xl:grid-cols-12">
        <aside class="panel xl:order-last xl:col-span-4" aria-labelledby="broadcast-audience-title">
            <h3 id="broadcast-audience-title" class="text-lg font-semibold">Audience</h3>
            <p class="mt-2 text-sm text-[var(--muted)]">Only verified seniors with valid mobile numbers are included.</p>
            <form method="GET" class="mt-5 grid gap-4">
                <label class="field-label">Target barangay
                    <select name="barangay_id">
                        <option value="">All barangays</option>
                        @foreach ($barangays as $barangay)<option value="{{ $barangay->id }}" @selected(request('barangay_id') == $barangay->id)>{{ $barangay->name }}</option>@endforeach
                    </select>
                </label>
                <button class="secondary-button">Update audience</button>
            </form>
            <dl class="mt-5 grid grid-cols-2 gap-4 border-t border-[var(--line)] pt-5">
                <div><dt class="text-sm text-[var(--muted)]">Eligible recipients</dt><dd class="mt-2 text-2xl font-semibold tabular-nums"><strong>{{ $eligible }}</strong></dd></div>
                <div><dt class="text-sm text-[var(--muted)]">Excluded</dt><dd class="mt-2 text-2xl font-semibold tabular-nums"><strong>{{ $excluded }}</strong></dd></div>
            </dl>
            <p class="mt-4 text-sm text-[var(--muted)]">Excluded records are ineligible or have a missing or invalid mobile number.</p>
        </aside>

        <form method="POST" action="{{ route('sms.blasts.store') }}" id="sms-broadcast-form" data-eligible="{{ $eligible }}" data-excluded="{{ $excluded }}" data-audience="{{ $barangays->firstWhere('id', request('barangay_id'))?->name ?: 'All barangays' }}" class="panel grid gap-5 xl:col-span-8">
            @csrf
            <input type="hidden" name="barangay_id" value="{{ request('barangay_id') }}">
            <h3 class="text-lg font-semibold">Compose message</h3>
            <label class="field-label">Quick template
                <select id="broadcast-template"><option value="">Write a message</option>@foreach ($templates as $template)<option value="{{ $template->id }}" data-body="{{ $template->body }}">{{ $template->name }}</option>@endforeach</select>
            </label>
            <label class="field-label">Message
                <textarea id="broadcast-message" name="message" rows="8" maxlength="1600" required aria-describedby="sms-count">{{ old('message') }}</textarea>
            </label>
            <p id="sms-count" class="text-sm text-[var(--muted)]" aria-live="polite">Enter a message to see SMS segment usage.</p>
            <label class="flex items-start gap-3 rounded-lg border border-[var(--line)] p-4 text-sm"><input type="checkbox" name="confirmed" value="1" required class="mt-1"> <span>I reviewed the audience and message, and confirm this broadcast.</span></label>
            <div class="staff-form-actions"><button type="submit" class="primary-button">Queue broadcast</button><a href="{{ route('sms.blasts.index') }}" class="secondary-button">Cancel</a></div>
        </form>
    </div>
@endsection
