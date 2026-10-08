@extends('layouts.app')
@section('content')
<h1 class="mb-6 text-3xl font-bold">Send individual SMS</h1>
<form method="POST" action="{{ route('sms.messages.store') }}" class="grid max-w-2xl gap-4 rounded-lg border bg-white p-5" onsubmit="return confirm('Queue this SMS for delivery?')">@csrf<label>Philippine mobile number <input name="recipient_number" type="tel" value="{{ old('recipient_number') }}" required placeholder="09xx xxx xxxx" class="mt-1 block w-full rounded border p-2"></label><label>Message <textarea name="message" rows="6" required maxlength="1600" class="mt-1 block w-full rounded border p-2">{{ old('message') }}</textarea></label><div class="flex gap-3"><a class="rounded border px-4 py-2" href="{{ route('sms.messages.index') }}">Cancel</a><button class="rounded bg-osca-primary px-4 py-2 text-white">Queue SMS</button></div></form>
@endsection
