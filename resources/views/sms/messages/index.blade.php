@extends('layouts.app')
@section('content')
<div class="min-w-0" x-data="smsMessages()">
    <x-workspace-navigation section="messaging" />
    <x-staff.page-header title="Individual SMS messages" description="Track queued messages, review provider results, and retry failed submissions.">
        <a class="primary-button" href="{{ route('sms.messages.create') }}">Send SMS</a>
    </x-staff.page-header>
    <p x-cloak x-show="notice" x-text="notice" role="status" class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200"></p>
    <x-staff.table label="Individual messages" class="rounded-lg border bg-white dark:border-slate-700 dark:bg-slate-900">
        <table class="w-full min-w-280 table-fixed text-left text-sm">
            <colgroup>
                <col class="w-[28%]">
                <col class="w-[34%]">
                <col class="w-[18%]">
                <col class="w-[10%]">
                <col class="w-[10%]">
            </colgroup>
            <thead class="bg-slate-100 dark:bg-slate-800">
                <tr>
                    @foreach (['Recipient', 'Message', 'Queued', 'Status', 'Action'] as $heading)
                        <th scope="col" class="p-3">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-slate-700">
                @forelse ($messages as $message)
                    @php
                        $details = [
                            'id' => $message->id,
                            'recipient' => $message->seniorCitizen?->full_name ?: $message->recipient_number,
                            'mobile' => $message->recipient_number,
                            'body' => $message->message,
                            'queued' => $message->queued_at?->format('Y-m-d H:i') ?? 'Not queued',
                            'sent' => $message->sent_at?->format('Y-m-d H:i') ?? 'Not sent',
                            'status' => ucfirst(strtolower($message->status->value)),
                            'failureReason' => $message->failure_reason,
                        ];
                    @endphp
                    <tr>
                        <td class="overflow-hidden p-3 pr-6"><span class="block truncate" title="{{ $details['recipient'] }}">{{ $details['recipient'] }}</span></td>
                        <td class="overflow-hidden p-3">
                            <button type="button" class="block w-full cursor-pointer rounded px-2 py-1 text-left text-osca-primary transition-colors hover:bg-blue-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary dark:text-blue-300 dark:hover:bg-slate-800"
                                data-message="{{ json_encode($details, JSON_THROW_ON_ERROR) }}"
                                x-on:click="openDetails(JSON.parse($el.dataset.message))"
                                aria-haspopup="dialog" aria-controls="sms-message-dialog" aria-label="View full message {{ $message->id }}">
                                <span class="block max-w-full truncate">{{ $message->message }}</span>
                            </button>
                        </td>
                        <td class="whitespace-nowrap p-3">{{ $message->queued_at?->format('Y-m-d H:i') ?: '—' }}</td>
                        <td class="whitespace-nowrap p-3"><span class="rounded px-2 py-1 text-xs font-medium" :class="statusClass(rowStatus({{ $message->id }}, @js($details['status'])))" x-text="rowStatus({{ $message->id }}, @js($details['status']))">{{ $details['status'] }}</span></td>
                        <td class="whitespace-nowrap p-3">
                            @if ($message->status === \App\Enums\SmsStatus::Failed)
                                <button type="button" class="secondary-button" data-retry="{{ json_encode($details, JSON_THROW_ON_ERROR) }}"
                                    x-show="rowStatus({{ $message->id }}, 'Failed') === 'Failed'" x-on:click="openRetry(JSON.parse($el.dataset.retry))" aria-haspopup="dialog" aria-controls="sms-retry-dialog">Retry</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-6 text-center">No messages have been queued yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-staff.table>
    <div class="mt-4">{{ $messages->links() }}</div>

    <x-sms.message-dialog />
    <x-sms.retry-dialog />
</div>
@endsection
