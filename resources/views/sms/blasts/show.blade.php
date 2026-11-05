@extends('layouts.app')
@section('content')
<x-workspace-navigation section="messaging" />
<a class="text-osca-primary underline" href="{{ route('sms.blasts.index') }}">Back to broadcasts</a><h2 class="my-4 text-3xl font-semibold">Broadcast #{{ $blast->id }}</h2>
<div class="grid gap-3 rounded-lg border bg-white p-5 sm:grid-cols-2"><p><strong>Audience:</strong> {{ $blast->barangay?->name ?: 'All barangays' }}</p><p><strong>Sender:</strong> {{ $blast->creator->first_name }} {{ $blast->creator->last_name }}</p><p><strong>Queued:</strong> {{ $blast->created_at->format('Y-m-d H:i') }}</p><p><strong>Eligible:</strong> {{ $blast->recipient_count }} · <strong>Excluded:</strong> {{ $blast->excluded_count }}</p><p class="whitespace-pre-wrap sm:col-span-2"><strong>Message:</strong> {{ $blast->message }}</p></div>
<h3 class="my-5 text-xl font-semibold">Recipient deliveries</h3>
<x-staff.table label="Broadcast recipient deliveries" class="rounded-lg border bg-white dark:border-slate-700 dark:bg-slate-900">
    <table class="w-full min-w-200 text-left text-sm">
        <thead class="bg-slate-100 dark:bg-slate-800">
            <tr>
                <th scope="col" class="p-3">OSCA ID</th>
                <th scope="col" class="p-3">Senior Name</th>
                <th scope="col" class="p-3">Mobile Number</th>
                <th scope="col" class="p-3">Failure Reason</th>
                <th scope="col" class="p-3">Status</th>
                <th scope="col" class="p-3">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-slate-700">
            @forelse ($messages as $message)
                <tr>
                    <td class="whitespace-nowrap p-3">{{ $message->seniorCitizen?->osca_id_number ?: 'Not issued' }}</td>
                    <td class="p-3">{{ $message->seniorCitizen?->full_name ?: 'Unlinked recipient' }}</td>
                    <td class="whitespace-nowrap p-3">{{ $message->recipient_number }}</td>
                    <td class="p-3">{{ $message->failure_reason ?: '—' }}</td>
                    <td class="p-3"><x-badge :status="$message->status" /></td>
                    <td class="p-3">
                        @if ($message->status === \App\Enums\SmsStatus::Failed)
                            @can('create', \App\Models\SmsMessage::class)
                                <form method="POST" action="{{ route('sms.messages.retry', $message) }}" onsubmit="return confirm('Resend this failed notification?')">
                                    @csrf
                                    <button type="submit" class="secondary-button" aria-label="Resend notification {{ $message->id }}">Resend</button>
                                </form>
                            @endcan
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-6 text-center">No recipient messages recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-staff.table>
<div class="mt-4">{{ $messages->links() }}</div>
@endsection
