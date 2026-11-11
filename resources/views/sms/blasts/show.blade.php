@extends('layouts.app')
@section('content')
<div class="w-full min-w-0" x-data="smsMessages({ action: 'Resend' })">
<x-workspace-navigation section="messaging" />
<header class="staff-page-heading">
    <div><h2>Broadcast details</h2><p>Review the original message and recipient delivery results.</p></div>
    <a href="{{ route('sms.blasts.index') }}" class="secondary-button" x-on:click="if (window.history.length > 1) { $event.preventDefault(); window.history.back(); }">&larr; Back</a>
</header>
<div class="grid gap-3 rounded-lg border border-slate-200 bg-white p-5 sm:grid-cols-2 dark:border-slate-700 dark:bg-slate-900">
    <p><strong>Audience:</strong> {{ $blast->barangay?->name ?: 'All barangays' }}</p>
    <p><strong>Sender:</strong> {{ $blast->creator->first_name }} {{ $blast->creator->last_name }}</p>
    <p><strong>Queued:</strong> {{ $blast->created_at->format('Y-m-d H:i') }}</p>
    <p><strong>Eligible:</strong> {{ $blast->recipient_count }} &middot; <strong>Excluded:</strong> {{ $blast->excluded_count }}</p>
</div>
<section class="panel mt-5" aria-labelledby="broadcast-message-heading">
    <h3 id="broadcast-message-heading" class="mb-3 text-base font-semibold">Broadcast message</h3>
    <p class="whitespace-pre-wrap wrap-anywhere text-sm leading-relaxed">{{ $blast->message }}</p>
</section>
<section class="w-full min-w-0" aria-labelledby="recipient-deliveries-title">
<h3 id="recipient-deliveries-title" class="my-5 text-xl font-semibold">Recipient deliveries</h3>
<p x-cloak x-show="notice" x-text="notice" role="status" class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200"></p>
<x-sms.delivery-table :messages="$messages" :broadcast="true" />
<div class="mt-4">{{ $messages->links() }}</div>
</section>
<x-sms.message-dialog :broadcast="true" />
<x-sms.retry-dialog :broadcast="true" />
</div>
@endsection
