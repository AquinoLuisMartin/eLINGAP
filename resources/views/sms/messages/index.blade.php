@extends('layouts.app')
@section('content')
<div class="min-w-0" x-data="{ selected: null }">
    <x-workspace-navigation section="messaging" />
    <x-staff.page-header title="Individual SMS messages" description="Track queued messages, review provider results, and retry failed submissions.">
        <a class="primary-button" href="{{ route('sms.messages.create') }}">Send SMS</a>
    </x-staff.page-header>
    <x-staff.table label="Individual messages" class="rounded-lg border bg-white dark:border-slate-700 dark:bg-slate-900">
        <table class="w-full min-w-280 table-fixed text-left text-sm">
            <colgroup>
                <col class="w-[22%]">
                <col class="w-[28%]">
                <col class="w-[14%]">
                <col class="w-[10%]">
                <col class="w-[18%]">
                <col class="w-[8%]">
            </colgroup>
            <thead class="bg-slate-100 dark:bg-slate-800">
                <tr>
                    @foreach (['Recipient', 'Message', 'Queued', 'Status', 'Failure reason', 'Action'] as $heading)
                        <th scope="col" class="p-3">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-slate-700">
                @forelse ($messages as $message)
                    @php
                        $details = [
                            'recipient' => $message->seniorCitizen?->full_name ?: $message->recipient_number,
                            'mobile' => $message->recipient_number,
                            'body' => $message->message,
                            'queued' => $message->queued_at?->format('Y-m-d H:i') ?? 'Not queued',
                            'sent' => $message->sent_at?->format('Y-m-d H:i') ?? 'Not sent',
                            'status' => ucfirst(strtolower($message->status->value)),
                        ];
                    @endphp
                    <tr>
                        <td class="wrap-break-word p-3">{{ $details['recipient'] }}</td>
                        <td class="p-3">
                            <button type="button" class="block w-full cursor-pointer rounded px-2 py-1 text-left text-osca-primary transition-colors hover:bg-blue-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-osca-primary dark:text-blue-300 dark:hover:bg-slate-800"
                                data-message="{{ json_encode($details, JSON_THROW_ON_ERROR) }}"
                                x-on:click="selected = JSON.parse($el.dataset.message); $refs.messageDialog.showModal()"
                                aria-haspopup="dialog" aria-controls="sms-message-dialog" aria-label="View full message {{ $message->id }}">
                                <span class="block max-w-full truncate">{{ $message->message }}</span>
                            </button>
                        </td>
                        <td class="whitespace-nowrap p-3">{{ $message->queued_at?->format('Y-m-d H:i') ?: '—' }}</td>
                        <td class="whitespace-nowrap p-3"><x-badge :status="$message->status" /></td>
                        <td class="wrap-break-word p-3">{{ $message->failure_reason ?: '—' }}</td>
                        <td class="whitespace-nowrap p-3">
                            @if ($message->status === \App\Enums\SmsStatus::Failed)
                                <form method="POST" action="{{ route('sms.messages.retry', $message) }}" onsubmit="return confirm('Retry this failed SMS?')">
                                    @csrf
                                    <button type="submit" class="text-osca-primary underline">Retry</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-6 text-center">No messages have been queued yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-staff.table>
    <div class="mt-4">{{ $messages->links() }}</div>

    <dialog id="sms-message-dialog" x-ref="messageDialog" x-on:close="selected = null" aria-labelledby="sms-message-title"
        class="m-auto max-h-[80dvh] w-[calc(100%-2rem)] max-w-2xl overflow-y-auto rounded-xl border border-slate-200 bg-white p-6 text-slate-900 shadow-xl backdrop:bg-slate-950/50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
        <div class="flex items-start justify-between gap-4">
            <h2 id="sms-message-title" class="text-xl font-semibold">SMS message</h2>
            <button type="button" class="secondary-button" autofocus x-on:click="$refs.messageDialog.close()">Close</button>
        </div>
        <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
            <div><dt class="text-slate-500">Recipient</dt><dd class="mt-1 wrap-break-word" x-text="selected?.recipient ?? ''"></dd></div>
            <div><dt class="text-slate-500">Mobile number</dt><dd class="mt-1" x-text="selected?.mobile ?? ''"></dd></div>
            <div><dt class="text-slate-500">Queued</dt><dd class="mt-1" x-text="selected?.queued ?? ''"></dd></div>
            <div><dt class="text-slate-500">Sent</dt><dd class="mt-1" x-text="selected?.sent ?? ''"></dd></div>
            <div><dt class="text-slate-500">Status</dt><dd class="mt-1" x-text="selected?.status ?? ''"></dd></div>
        </dl>
        <div class="mt-6 border-t border-slate-200 pt-5 dark:border-slate-700">
            <h3 class="text-sm font-semibold">Full message</h3>
            <p class="mt-3 whitespace-pre-wrap wrap-anywhere text-sm leading-relaxed" x-text="selected?.body ?? ''"></p>
        </div>
    </dialog>
</div>
@endsection
