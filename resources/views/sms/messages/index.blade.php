@extends('layouts.app')
@section('content')
<div class="min-w-0" x-data="smsMessages()">
    <x-workspace-navigation section="messaging" />
    <x-staff.page-header title="Individual SMS messages" description="Track queued messages, review provider results, and retry failed submissions.">
        <a class="primary-button" href="{{ route('sms.messages.create') }}">Send SMS</a>
    </x-staff.page-header>
    <form class="mb-5 flex flex-wrap items-end gap-3" method="GET" action="{{ route('sms.messages.index') }}">
        <div class="ui-field w-full sm:w-56">
            <label for="sms-status">Delivery status</label>
            <x-select id="sms-status" name="status">
                <option value="">All statuses</option>
                @foreach (\App\Enums\SmsStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst(strtolower($status->value)) }}</option>
                @endforeach
            </x-select>
        </div>
        <x-button type="submit" variant="secondary">Apply filter</x-button>
        @if (request()->filled('status'))
            <a class="secondary-button" href="{{ route('sms.messages.index') }}">Clear filter</a>
        @endif
    </form>
    <p x-cloak x-show="notice" x-text="notice" role="status" class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200"></p>
    <x-sms.delivery-table :messages="$messages" />
    <div class="mt-4">{{ $messages->links() }}</div>

    <x-sms.message-dialog />
    <x-sms.retry-dialog />
</div>
@endsection
