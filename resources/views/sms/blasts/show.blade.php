@extends('layouts.app')
@section('content')
<div class="w-full min-w-0" x-data="smsMessages({ action: 'Resend' })">
<x-workspace-navigation section="messaging" />
<a class="text-osca-primary underline" href="{{ route('sms.blasts.index') }}">Back to broadcasts</a><h2 class="my-4 text-3xl font-semibold">Broadcast #{{ $blast->id }}</h2>
<div class="grid gap-3 rounded-lg border bg-white p-5 sm:grid-cols-2"><p><strong>Audience:</strong> {{ $blast->barangay?->name ?: 'All barangays' }}</p><p><strong>Sender:</strong> {{ $blast->creator->first_name }} {{ $blast->creator->last_name }}</p><p><strong>Queued:</strong> {{ $blast->created_at->format('Y-m-d H:i') }}</p><p><strong>Eligible:</strong> {{ $blast->recipient_count }} · <strong>Excluded:</strong> {{ $blast->excluded_count }}</p><p class="whitespace-pre-wrap sm:col-span-2"><strong>Message:</strong> {{ $blast->message }}</p></div>
<section class="w-full min-w-0" aria-labelledby="recipient-deliveries-title">
<h3 id="recipient-deliveries-title" class="my-5 text-xl font-semibold">Recipient deliveries</h3>
<p x-cloak x-show="notice" x-text="notice" role="status" class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200"></p>
<x-staff.table label="Broadcast recipient deliveries" class="rounded-lg border bg-white dark:border-slate-700 dark:bg-slate-900">
    <table class="w-full table-auto text-left text-sm">
        <thead class="bg-slate-100 dark:bg-slate-800">
            <tr>
                @foreach (['OSCA ID', 'Name', 'Mobile number', 'Message', 'Queued', 'Status', 'Action'] as $heading)
                    <th scope="col" @class(['px-4! py-3!', 'text-left' => ! in_array($heading, ['Status', 'Action']), 'text-center' => $heading === 'Status', 'pr-6! text-right' => $heading === 'Action'])>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y dark:divide-slate-700">
            @forelse ($messages as $message)
                @php
                    $details = [
                        'id' => $message->id,
                        'oscaId' => $message->seniorCitizen?->osca_id_number ?: 'Not issued',
                        'recipient' => $message->seniorCitizen?->full_name ?: 'Unlinked recipient',
                        'mobile' => $message->recipient_number,
                        'body' => $message->message,
                        'queued' => $message->queued_at?->format('Y-m-d H:i') ?? 'Not queued',
                        'sent' => $message->sent_at?->format('Y-m-d H:i') ?? 'Not sent',
                        'status' => ucfirst(strtolower($message->status->value)),
                        'failureReason' => $message->failure_reason,
                    ];
                @endphp
                <tr>
                    <td class="whitespace-nowrap px-4! py-3! text-left">{{ $details['oscaId'] }}</td>
                    <td class="w-auto whitespace-nowrap px-4! py-3! text-left">{{ $details['recipient'] }}</td>
                    <td class="whitespace-nowrap px-4! py-3! text-left">{{ $details['mobile'] }}</td>
                    <td class="px-4! py-3! text-left">
                        <button type="button" class="block w-56 cursor-pointer truncate rounded text-left text-osca-primary hover:bg-blue-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary dark:text-blue-300 dark:hover:bg-slate-800"
                            data-message="{{ json_encode($details, JSON_THROW_ON_ERROR) }}" x-on:click="openDetails(JSON.parse($el.dataset.message))"
                            aria-haspopup="dialog" aria-controls="sms-message-dialog" aria-label="View message {{ $message->id }}">{{ $details['body'] }}</button>
                    </td>
                    <td class="whitespace-nowrap px-4! py-3! text-left">{{ $message->queued_at?->format('Y-m-d H:i') ?: '—' }}</td>
                    <td class="whitespace-nowrap px-4! py-3! text-center"><span class="rounded px-2 py-1 text-xs font-medium" :class="statusClass(rowStatus({{ $message->id }}, @js($details['status'])))" x-text="rowStatus({{ $message->id }}, @js($details['status']))">{{ $details['status'] }}</span></td>
                    <td class="w-32 min-w-32 py-3! pl-4! pr-6! text-right">
                        <div class="flex flex-wrap justify-end gap-2">
                            @if ($message->status === \App\Enums\SmsStatus::Failed)
                                @can('create', \App\Models\SmsMessage::class)
                                    <button type="button" class="secondary-button" data-retry="{{ json_encode($details, JSON_THROW_ON_ERROR) }}"
                                        x-show="rowStatus({{ $message->id }}, 'Failed') === 'Failed'" x-on:click="openRetry(JSON.parse($el.dataset.retry))"
                                        aria-haspopup="dialog" aria-controls="sms-retry-dialog" aria-label="Resend notification {{ $message->id }}">Resend</button>
                                @endcan
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="p-6 text-center">No recipient messages recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-staff.table>
<div class="mt-4">{{ $messages->links() }}</div>
</section>
<x-sms.message-dialog :broadcast="true" />
<x-sms.retry-dialog :broadcast="true" />
</div>
@endsection
