@extends('layouts.app')
@section('content')
<x-workspace-navigation section="messaging" />
<x-staff.page-header title="Send individual SMS" description="Check the recipient number and message before placing it in the delivery queue." />
<form method="POST" action="{{ route('sms.messages.store') }}" class="grid max-w-2xl gap-4 rounded-lg border bg-white p-5" data-confirm-title="Queue this SMS for delivery?" data-confirm="The message is sent to the number entered." data-confirm-label="Queue SMS">@csrf<label>Philippine mobile number <input name="recipient_number" type="tel" value="{{ old('recipient_number') }}" required placeholder="09xx xxx xxxx" class="mt-1 block w-full rounded border p-2"></label><label>Message <textarea name="message" rows="6" required maxlength="1600" class="mt-1 block w-full rounded border p-2">{{ old('message') }}</textarea></label><div class="flex gap-3"><a class="secondary-button" href="{{ route('sms.messages.index') }}">Cancel</a><x-button type="submit">Queue SMS</x-button></div></form>
@endsection
