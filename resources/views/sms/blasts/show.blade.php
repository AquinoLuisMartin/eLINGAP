@extends('layouts.app')
@section('content')
<div x-data="smsMessages({ action: 'Resend' })">
<x-workspace-navigation section="messaging" />
<a class="text-osca-primary underline" href="{{ route('sms.blasts.index') }}">Back to broadcasts</a><h2 class="my-4 text-3xl font-semibold">Broadcast #{{ $blast->id }}</h2>
<div class="grid gap-3 rounded-lg border bg-white p-5 sm:grid-cols-2"><p><strong>Audience:</strong> {{ $blast->barangay?->name ?: 'All barangays' }}</p><p><strong>Sender:</strong> {{ $blast->creator->first_name }} {{ $blast->creator->last_name }}</p><p><strong>Queued:</strong> {{ $blast->created_at->format('Y-m-d H:i') }}</p><p><strong>Eligible:</strong> {{ $blast->recipient_count }} · <strong>Excluded:</strong> {{ $blast->excluded_count }}</p><p class="whitespace-pre-wrap sm:col-span-2"><strong>Message:</strong> {{ $blast->message }}</p></div>
<h3 class="my-5 text-xl font-semibold">Recipient deliveries</h3>
<p x-cloak x-show="notice" x-text="notice" role="status" class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200"></p>
<x-staff.table label="Broadcast recipient deliveries" class="rounded-lg border bg-white dark:border-slate-700 dark:bg-slate-900">
    <table class="w-full min-w-260 table-fixed text-left text-sm">
        <colgroup><col class="w-[16%]"><col class="w-[30%]"><col class="w-[22%]"><col class="w-[12%]"><col class="w-[20%]"></colgroup>
        <thead class="bg-slate-100 dark:bg-slate-800">
            <tr>
                @foreach (['OSCA ID', 'Senior Name', 'Mobile Number', 'Status', 'Action'] as $heading)
                    <th scope="col" class="p-3">{{ $heading }}</th>
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
                    <td class="wrap-anywhere p-3">{{ $details['oscaId'] }}</td>
                    <td class="overflow-hidden p-3 pr-6"><span class="block truncate" title="{{ $details['recipient'] }}">{{ $details['recipient'] }}</span></td>
                    <td class="whitespace-nowrap p-3">{{ $details['mobile'] }}</td>
                    <td class="whitespace-nowrap p-3"><span class="rounded px-2 py-1 text-xs font-medium" :class="statusClass(rowStatus({{ $message->id }}, @js($details['status'])))" x-text="rowStatus({{ $message->id }}, @js($details['status']))">{{ $details['status'] }}</span></td>
                    <td class="p-3">
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="secondary-button" data-message="{{ json_encode($details, JSON_THROW_ON_ERROR) }}"
                                x-on:click="openDetails(JSON.parse($el.dataset.message))" aria-haspopup="dialog" aria-controls="sms-message-dialog" aria-label="View message {{ $message->id }}">Details</button>
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
                <tr><td colspan="5" class="p-6 text-center">No recipient messages recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-staff.table>
<div class="mt-4">{{ $messages->links() }}</div>
<x-sms.message-dialog :broadcast="true" />
<x-sms.retry-dialog :broadcast="true" />
</div>
@endsection
