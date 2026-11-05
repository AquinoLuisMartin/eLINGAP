@extends('layouts.app')
@section('content')
<div class="min-w-0" x-data="smsMessages()">
    <x-workspace-navigation section="messaging" />
    <x-staff.page-header title="Individual SMS messages" description="Track queued messages, review provider results, and retry failed submissions.">
        <a class="primary-button" href="{{ route('sms.messages.create') }}">Send SMS</a>
    </x-staff.page-header>
    <p x-cloak x-show="notice" x-text="notice" role="status" class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200"></p>
    <x-sms.delivery-table :messages="$messages" />
    <div class="mt-4">{{ $messages->links() }}</div>

    <x-sms.message-dialog />
    <x-sms.retry-dialog />
</div>
@endsection
