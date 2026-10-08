@extends('layouts.app')
@section('content')
<a href="{{ route('sms.blasts.index') }}" class="text-sm text-osca-primary underline">Back to messaging</a>
<h1 class="mt-3 text-3xl font-bold">New SMS broadcast</h1>
<p class="mb-6 text-slate-600">Send a message to verified seniors with valid mobile numbers.</p>
<form method="GET" class="mb-5 flex flex-wrap items-end gap-3 rounded-lg border bg-white p-4"><label>Target barangay <select name="barangay_id" class="mt-1 block rounded border p-2"><option value="">All barangays</option>@foreach ($barangays as $barangay)<option value="{{ $barangay->id }}" @selected(request('barangay_id') == $barangay->id)>{{ $barangay->name }}</option>@endforeach</select></label><button class="rounded border px-4 py-2">Update audience</button></form>
<div class="mb-5 grid gap-3 sm:grid-cols-2"><div class="rounded-lg border bg-white p-4"><p>Eligible recipients</p><strong class="text-2xl">{{ $eligible }}</strong></div><div class="rounded-lg border bg-white p-4"><p>Excluded: ineligible, missing or invalid mobile</p><strong class="text-2xl">{{ $excluded }}</strong></div></div>
<form method="POST" action="{{ route('sms.blasts.store') }}" id="sms-broadcast-form" data-eligible="{{ $eligible }}" data-excluded="{{ $excluded }}" data-audience="{{ $barangays->firstWhere('id', request('barangay_id'))?->name ?: 'All barangays' }}" class="grid max-w-3xl gap-4 rounded-lg border bg-white p-5">
    @csrf <input type="hidden" name="barangay_id" value="{{ request('barangay_id') }}">
    <label>Quick template <select id="broadcast-template" class="mt-1 w-full rounded border p-2"><option value="">Write a message</option>@foreach ($templates as $template)<option value="{{ $template->id }}" data-body="{{ $template->body }}">{{ $template->name }}</option>@endforeach</select></label>
    <label>Message <textarea id="broadcast-message" name="message" rows="7" maxlength="1600" required class="mt-1 w-full rounded border p-2">{{ old('message') }}</textarea></label>
    <p id="sms-count" class="text-sm text-slate-600" aria-live="polite">Enter a message to see SMS segment usage.</p>
    <label class="rounded bg-slate-50 p-3"><input type="checkbox" name="confirmed" value="1" required> I reviewed the audience and message, and confirm this broadcast.</label>
    <button type="submit" class="rounded bg-osca-primary px-4 py-2 font-semibold text-white">Queue broadcast</button>
</form>
@endsection
